<?php

namespace App\Http\Livewire;

use App\Models\Chat;
use App\Models\Contact;
use App\Models\Message;
use Livewire\Component;
use Illuminate\Support\Facades\Notification;

class ChatComponent extends Component
{
    public $search;

    public $contactChat;
    public $chat;
    public $chat_id;

    public $bodyMessage;

    //Lista de usuarios conectados
    public $users;

    //Escuchar eventos privados
    public function getListeners()
    {
        $user_id = auth()->user()->id;

        return [
            "echo-notification:App.Models.User.{$user_id},notification" => "render",
            "echo-presence:chat.1,here" => 'chatHere',
            "echo-presence:chat.1,joining" => 'chatJoining',
            "echo-presence:chat.1,leaving" => 'chatLeaving',
        ];
    }

    public function mount() 
    {
        $this->users = \collect();
    }

    public function render()
    {
        if ($this->chat) {

            $this->chat->messages()
                        ->where('user_id', '!=', auth()->user()->id)
                        ->where('is_read', false)
                        ->update([
                            'is_read' => true
                        ]);

        $chat = Chat::find($this->chat_id);
        //Mandar una notificacion al usuario
        if ($chat->unread_messages_count) {
            Notification::send($this->user_notifications, new \App\Notifications\NewMessage());
        }

            $this->emit('scrollIntoView');
        }

        return view('livewire.chat-component')->layout('layouts.chat');
    }

    //Ciclo de vida de livewire
    public function updatedBodyMessage($value)
    {
        if ($value != null) {
            if ($this->chat != null) {
              Notification::send($this->user_notifications, new \App\Notifications\UserTyping($this->chat->id));
            }
        }
    }

    //Propridad computada
    public function getContactsProperty()
    {
        return Contact::where('user_id', \auth()->user()->id)
            ->when($this->search, function ($query) {

                $query->where(function ($query) {
                    $query->where('name', 'LIKE', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($query) {
                            $query->where('email', 'LIKE', '%' . $this->search . '%');
                        });
                });
            })->get() ?? [];
    }

    //recuperar los mensajes por una propiedad computada
    public function getMessagesProperty()
    {

        return $this->chat ? $this->chat->messages()->get() : [];
    }

    //Recuperar todos los chats existentes para un usuario
    public function getChatsProperty()
    {
        return auth()->user()->chats()->get()->sortByDesc('last_message_at');
    }

    //Recuperar a los usuarios que se va a notificar
    public function getUserNotificationsProperty()
    {
        return $this->chat ? $this->chat->users->where('id', '!=', auth()->user()->id) : \collect();
    }

    //chat activo propiedad computada
    public function getActiveProperty()
    {
        //return $this->users->contains($this->user_notifications->first()->id);
        //solucion temporal
        return $this->user_notifications->count() != 0 ? $this->users->contains($this->user_notifications->first()->id) : false;
    }

    public function open_chat_contact(Contact $contact)
    {
        $chat = auth()->user()->chats()
            ->whereHas('users', function ($query) use ($contact) {
                $query->where('user_id', $contact->contact_id);
            })
            ->has('users', 2)
            ->first();

        if ($chat) {
            $this->chat = $chat;
            $this->chat_id = $chat->id;
            $this->reset('contactChat', 'bodyMessage', 'search');
        } else {
            $this->contactChat = $contact;
            $this->reset('chat', 'bodyMessage', 'search');
        }
    }

    //abrir la vista de mensajes de un chat
    public function open_chat(Chat $chat)
    {
        $this->chat = $chat;
        $this->chat_id = $chat->id;
        $this->reset('contactChat', 'bodyMessage');
        
    }

    public function sendMessage()
    {
        $this->validate([
            'bodyMessage' => 'required'
        ]);

        //Verificar si existe un chat con el contacto
        if (!$this->chat) {
            $this->chat = Chat::create();
            $this->chat_id = $this->chat->id;

            $this->chat->users()->attach([
                auth()->user()->id,
                $this->contactChat->contact_id
            ]);
        }


        $this->chat->messages()->create([
            'body' => $this->bodyMessage,
            'user_id' => auth()->user()->id,
        ]);

        //Mandar una notificacion al usuario
        Notification::send($this->user_notifications, new \App\Notifications\NewMessage());

        $this->reset('bodyMessage', 'contactChat');
    }

    //Saber que usuarios estan conectados
    public function chatHere($users)
    {
        $this->users = \collect($users)->pluck('id');
    }

    //Saber que usuarios se conectaron
    public function chatJoining($user)
    {
        $this->users->push($user['id']);
    }

    //Saber que usuarios se desconectaron
    public function chatLeaving($user)
    {
        $this->users = $this->users->filter(function ($id) use ($user) {
            return $id != $user['id'];
        });
    }
}
