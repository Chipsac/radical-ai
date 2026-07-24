<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index()
    {
        return view('crm.leads', [
            'leads' => Lead::with('owner')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        Lead::create($data + [
            'status' => 'new',
            'owner_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Lead added.');
    }
}
