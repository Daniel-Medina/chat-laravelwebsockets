<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Rules\InvalidEmail;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $contacts = \auth()->user()->contacts()->paginate(10);

        return \view('contacts.index', \compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return \view('contacts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'name' => 'required',
            'email' => [
                        'required', 
                        'email', 
                        'exists:users', 
                        Rule::notIn([\auth()->user()->email]),
                        new InvalidEmail,
            ],
        ]);

        $user = User::where('email', $request->email)->first();

        $contact = Contact::create([
            'name' => $request->name,
            'user_id' => \auth()->user()->id,
            'contact_id' => $user->id,
        ]);

        \session()->flash('flash.banner', 'El contacto se ha creado correctamente');
        \session()->flash('flash.bannerStyle', 'success');

        return \redirect()->route('contacts.edit', \compact('contact'))->with('info');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        //
        return \view('contacts.edit', \compact('contact'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact)
    {
        $request->validate([
            'name' => 'required',
            'email' => [
                        'required', 
                        'email', 
                        'exists:users', 
                        Rule::notIn([\auth()->user()->email]),
                        new InvalidEmail($contact->user->email),
            ],
        ]);

        $user = User::where('email', $request->email)->first();

        $contact->update([
            'name' => $request->name,
            'contact_id' => $user->id,
        ]);

        \session()->flash('flash.banner', 'El contacto se ha actualizado correctamente');
        \session()->flash('flash.bannerStyle', 'success');

        return \redirect()->route('contacts.edit', \compact('contact'))->with('info');

        return $request->all();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        \session()->flash('flash.banner', 'El contacto se ha eliminado correctamente');
        \session()->flash('flash.bannerStyle', 'success');

        return \redirect()->route('contacts.index', \compact('contact'))->with('info');
    }
}
