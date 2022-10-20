<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_url',
        'is_group',
    ];

    //Uno a muchos

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    //Muchos a muchos
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    //crear un mutador que alterne el nombre de acuerdo al campo is group
    public function name() :Attribute {
        return new Attribute(
            get: function($value) {
                if ($this->is_group) {
                    return $value;
                }

                $user = $this->users->where('id', '!=', auth()->user()->id)->first();
                $contact = auth()->user()->contacts()->where('contact_id', $user->id)->first();

                return $contact ? $contact->name : $user->email;
            }
        );
    }

    //crear un mutador que alterne la imagen de acuerdo al campo is group
    public function image() :Attribute {
        return new Attribute(
            get: function() {
                if ($this->is_group) {
                   return Storage::url($this->image_url);
                }

                $user = $this->users->where('id', '!=', auth()->user()->id)->first();

                return $user->profile_photo_url;
            }
        );
    }

    //mutudor para conocer la hora del ultimo mensaje
    public function lastMessageAt() :Attribute {
        return new Attribute(
            get: function() {
                return $this->messages->last()->created_at;
            }
        );
    }

    //accesor para mensajes no leidos
    public function unreadMessagesCount() :Attribute
    {
        return new Attribute(
            get: function() {
                return $this->messages()->where('user_id', '!=', auth()->user()->id)->where('is_read', false)->count();
            }
        );
    }
}
