<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Jobs\SendEmailJob;
use App\Models\Position;
use App\Models\User;
use League\Csv\Reader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class ServidorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index():View
    {
        Gate::authorize('acesso-restrito-servidor');
        $servidores = User::where('role_id', UserRole::SERVIDOR)->paginate(10);
        return view('servidor.index', ['servidores' => $servidores]);

    }

      /**
     * Search Name the specified resource from storage.
     */
    public function searchName()
    {
        Gate::authorize('acesso-restrito-servidor');
        $search = request('search');
        if($search){
            $servidores = User::where([
                ['name','like','%'.$search.'%']
            ])->get();
            return view('servidor.index', ['servidores' => $servidores,'search' => $search]);
        }else{
            $servidores = User::where('role_id', UserRole::SERVIDOR)->paginate(10);
            return view('servidor.index', ['servidores' => $servidores]);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create():View
    {
        Gate::authorize('acesso-restrito-servidor');
        $positions = Position::all();
        return view('servidor.create', ['positions' => $positions]);
    }

    /**
     * Show the form for upload a file.
     */
    public function renderUpload(): View
    {
        Gate::authorize('acesso-restrito-servidor');
        return view('servidor.upload');
    }

    /**
     * Upload data on database
     */
    public function uploadServer(Request $request)
    {
        Gate::authorize('acesso-restrito-servidor');
        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');
            $path = $file->store('csv_files');

            $csv = Reader::createFromPath(storage_path('app/' . $path), 'r');
            $csv->setHeaderOffset(0);

            foreach ($csv as $row) {
                $password = Str::random(10);

                $server = new User();
                $server->name = $row['name'];
                $server->email = $row['email'];
                $server->cpf = $row['cpf'];
                $server->password = Hash::make($password);
                $server->position_id = $row['position_id'];
                $server->save();

                SendEmailJob::dispatch($server, $password);
            }
        }

        return redirect()->route('servidores');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('acesso-restrito-servidor');
        $request->validate(
            [
                'name'  => 'required|string',
                'email'  => 'required|email',
                'cpf' => 'required|string',
                'position_id' => 'required|numeric'


            ],
            [
                'name.required' => 'Campo nome é obrigatório',
                'email.email' => 'Necessário um email válido',
                'email.required' => 'Campo email é obrigatório',
                'cpf.required' => 'Campo cpf é obrigatório',
                'position_id.numeric' => 'Selecione o cargo!'

            ]
        );

        $password = Str::random(10);

        $server = new User();
        $server->name = $request->input('name');
        $server->email = $request->input('email');
        $server->cpf = $request->input('cpf');
        $server->password = Hash::make($password);
        $server->position_id = $request->input('position_id');
        $server->save();

        SendEmailJob::dispatch($server, $password);

        return redirect()->route('servidores');
    }

    /**
     * Display the specified resource.
     */
    public function dashboard()
    {
        Gate::authorize('acesso-restrito-servidor');
        $user = auth()->user();
        $portarias = null;

        if($user->role_id == UserRole::SERVIDOR) {
            $portarias = $user->ordinances()->get();
        }

        return view('dashboard', compact('portarias', 'user'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        Gate::authorize('acesso-restrito-servidor');
        $servidor = User::where('id', $id)->first();
        $positions = Position::all();

        return view('servidor.edit', [
            'servidor' => $servidor,
            'positions' => $positions
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('acesso-restrito-servidor');
        $validatedData = $request->validate(
            [
                'name' => 'required|string',
                'email' => 'required|email',
                'cpf' => 'required|string',
                'position_id' => 'required|numeric'
            ],
            [
                'name.required' => 'Campo nome é obrigatório',
                'email.email' => 'Necessário um email válido',
                'email.required' => 'Campo email é obrigatório',
                'cpf.required' => 'Campo cpf é obrigatório',
                'position_id.numeric' => 'Selecione o cargo!'
            ]
        );

        $newData = User::find($id);

        $newData->name = $validatedData['name'];
        $newData->cpf = $validatedData['cpf'];
        $newData->email = $validatedData['email'];
        $newData->position_id = $validatedData['position_id'];

        $newData->save();

        return redirect()->route('servidores');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        Gate::authorize('acesso-restrito-servidor');
        $servidor = User::findOrFail($id);

        if ($request->input('server-name') !== $servidor->name) {
            return redirect()->route('servidores');
        }


        $servidor->delete();

        return redirect()->route('servidores');
    }
}
