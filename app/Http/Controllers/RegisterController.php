<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;

class RegisterController extends Controller
{
  public function index() {
    return view('/register');
  }

  public function store(Request $request){

      $user = new User;
      $user -> id_user = uniqid();
      $user -> name = request("name");
      $user -> surname = request("surname");
      $user -> birth_date = request("birth_date");
      $user -> email = request("email");
      $user -> pwd_hash = bcrypt("pwd_hash");
      $user -> citta = request("citta");
      $user -> gender = request("gender");
      $user -> pic_path = request("pic_path");
      $user -> save();

      #sarebbe da fare la redirect con l'utente già loggato
      //Daniele: non credo sia una buona idea, dobbiamo mandare una mail e confermare l'utente
      return redirect('/home');
  }

}
