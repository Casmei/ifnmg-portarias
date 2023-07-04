<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Models\Ordinance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class OrdinanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('acesso-restrito-servidor');
        $portarias = Ordinance::all();

        foreach ($portarias as $portaria) {
            $now = Carbon::now();
            $portaria->startDateFormatted = Carbon::parse($portaria->start_date)->format('d/m/Y');
            $portaria->endDateFormatted = Carbon::parse($portaria->end_date)->format('d/m/Y');

            if ($now->isBetween($portaria->start_date, $portaria->end_date)) {
                $portaria->status = true;
            } else {
                $portaria->status = false;
            }
        }

        return view('portaria.index', compact('portarias'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('acesso-restrito-servidor');
        $servidores = User::where('role_id', UserRole::SERVIDOR)->get();
        return view('portaria.create', ['servidores' => $servidores]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('acesso-restrito-servidor');
        $request->validate([
            'ordinance_number' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'campus' => 'required',
            'description' => 'required',
            'pdf_file' => 'required|mimes:pdf|max:2048',
            'servidores' => 'required|array',
        ], [
            'ordinance_number.required' => 'O número da portaria é obrigatório.',
            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'end_date.required' => 'A data de término é obrigatória.',
            'end_date.date' => 'A data de término deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
            'campus.required' => 'O campus é obrigatório.',
            'description.required' => 'A descrição é obrigatória.',
            'pdf_file.required' => 'O arquivo PDF é obrigatório.',
            'pdf_file.mimes' => 'O arquivo deve ser do tipo PDF.',
            'pdf_file.max' => 'O tamanho máximo permitido para o arquivo PDF é de 2MB.',
            'servidores.required' => 'É necessário selecionar pelo menos um servidor.',
            'servidores.array' => 'A lista de servidores deve ser um array.',
        ]);

        $ordinance = new Ordinance();
        $ordinance->number = $request->input('ordinance_number');
        $ordinance->start_date = $request->input('start_date');
        $ordinance->end_date = $request->input('end_date');
        $ordinance->campus = $request->input('campus');
        $ordinance->description = $request->input('description');

        if ($request->input('visibility')) {
            $ordinance->visibility = true;
        } else {
            $ordinance->visibility = false;
        }

        if ($request->hasFile('pdf_file')) {
            $pdfPath = $request->file('pdf_file')->store('public/portarias');
            $pdfUrl = Storage::url($pdfPath);
            $ordinance->pdf_url = $pdfUrl;
        }

        $ordinance->save();

        $servidores = $request->input('servidores');
        $ordinance->users()->sync($servidores);
        return redirect()->route('ordinance');
    }


    public function download($id)
    {
        $ordinance = Ordinance::findOrFail($id);

        // Verifique se a URL do arquivo PDF está disponível
        if (!empty($ordinance->pdf_url)) {
            $filePath = str_replace('/storage', 'public', $ordinance->pdf_url);
            return Storage::download($filePath);
    }

        // Redirecione ou exiba uma mensagem de erro, caso não tenha PDF disponível
        return redirect()->back()->with('error', 'Não há PDF disponível para download');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        Gate::authorize('acesso-restrito-servidor');
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        Gate::authorize('acesso-restrito-servidor');
        $portaria = Ordinance::where('id', $id)->first();
        // $positions = Position::all();

        // return view('ordinance.edit', compact('portaria'));

        return view('portaria.edit', [
            'portaria' => $portaria,
            // 'positions' => $positions
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('acesso-restrito-servidor');
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('acesso-restrito-servidor');
        //
    }
}
