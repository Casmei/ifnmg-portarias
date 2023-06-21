<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Jobs\SendEmailJob;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ServerCredentialsNotification;
use Spatie\SimpleExcel\SimpleExcelReader;
use League\Csv\Reader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServidorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index():View
    {
        $servidores = User::where('role_id', UserRole::SERVIDOR)->paginate(10);
        return view('servidor.index', ['servidores' => $servidores]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create():View
    {
        $positions = Position::all();
        return view('servidor.create', ['positions' => $positions]);
    }

    /**
     * Show the form for upload a file.
     */
    public function renderUpload(): View
    {
        return view('servidor.upload');
    }

    /**
     * Upload data on database
     */
    public function uploadServer(Request $request)
    {
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

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
        return redirect()->route('servidores');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
