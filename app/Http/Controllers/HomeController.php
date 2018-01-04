<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Page;
use App\Post;
use App\Users_make_friends;
use App\Users_follow_pages;
use App\PostUser;
use App\PostPage;
use App\CommentUser;
use App\CommentU;
use App\CommentP;
use App\LikePost;

use Illuminate\Support\Facades\DB;

use Cookie;

class HomeController extends Controller{

  function array_flatten($array) {
    $return = array();
    foreach ($array as $key => $value) {
        if (is_array($value)){
            $return = array_merge($return, array_flatten($value));
        } else {
            $return[$key] = $value;
        }
    }

    return $return;
}

  function cmp($a, $b) {
      if ($a['created_at'] == $b['created_at']) return 0;
      return (strtotime($a['created_at']) < strtotime($b['created_at'])) ? 1 : -1;
  }

  public function verify_cookie(){
    if (Cookie::has('session')){
      //conrollo che l'id presente nel cookie esista nel db
      return true;
    }
    else{
      return false;
    }
  }

  //funzione richiamata quando viene richiesta la root del nostro sito
  public function landing()
  {
    if($this->verify_cookie()){

      //login
      $id = Cookie::get('session');
      $logged_user = User::where('id_user', $id)->first();

      $friends = $logged_user::friends($id);     //Torna un array con gli amici
      $requests = $logged_user::pendingfriends($id);   //Torna un array con le richieste
      $suggested_friends = User::where('citta', $logged_user['citta'])->where('roles', 0)->get();

      //pagine seguite
      $followed_pages_id = Users_follow_pages::where('id_user', $id)->get();

      //Caricamento dei post degli amici e delle pagine
      $posts = array();
      $list_comments = array();
      $likes = array();

      //inserisco anche i miei posts
      array_push($posts, Post::where('id_author', $id)->orderBy('created_at', 'asc')->get());


      //per ogni elemento di friends devo andare nella tabella post_users e tirare fuori tutti gli id dei post di ogni mio amico
      foreach ($friends as $friend){
        $id_posts = PostUser::where('id_user', $friend['id_user'])->get();
        foreach ($id_posts as $post){
          //post
          array_push($posts, Post::where('id_post', $post['id_post'])->orderBy('created_at','asc')->get());
          //commenti
          array_push($list_comments, CommentU::where('id_post', $post['id_post'])->orderBy('updated_at', 'asc')->get());
          //likes
          array_push($likes, LikePost::where('id_post', $post['id_post'])->get());
        }
      }

      foreach ($followed_pages_id as $id_page){
        $id_page_post = PostPage::where('id_page', $id_page['id_page'])->get();
        foreach ($id_page_post as $post_page){
          array_push($posts, Post::where('id_post', $post_page['id_post'])->get());
        }
      }

      //gli passo il controller stesso così posso richiamare le funzioni direttamente dalle views
      $controller = $this;

      //ordino per data
      $posts = array_flatten($posts);
      $list_comments = array_flatten($list_comments);

      usort($posts, array($this, 'cmp'));
      usort($list_comments, array($this, 'cmp'));

      return view('home', compact('logged_user', 'posts', 'list_comments','controller','friends', 'suggested_friends'));

    }
    else{
      return redirect('/login');
    }
  }

  public function newPost(Request $request){
    //verifica dei campi
    $id_user = Cookie::get('session');
    $post = new Post;
    $post->id_author = $id_user;
    $post->created_at = now();
    $post->updated_at = now();
    $post->content = request('content');
    $post->fixed = 0;     //ovviamente da verificare
    $post->save();

    $post_tmp = Post::where('id_author', $id_user)->where('content', request('content'))->first();

    DB::table('posts_user')->insert(['id_user' => $id_user, 'id_post' => $post_tmp['id_post']]);

    //che bello ajax qui....
    return redirect('/');
  }

  //prendendo in ingresso un id, restituisce l'utente relativo
  //es. da un post id_author--->id_user
  public function ShowUser($id){
    if(is_numeric($id)){
      $user = Page::where('id_page', $id)->first();
    }
    else{
      $user = User::where('id_user', $id)->first();
    }
    return $user;
  }

  public function PrintName($id){
    if(is_numeric($id)){
      $user = Page::where('id_page', $id)->first();
      return ($user['nome']);
    }
    else{
      $user = User::where('id_user', $id)->first();
      return ($user['name'] . ' ' . $user['surname']);
    }

  }

}
