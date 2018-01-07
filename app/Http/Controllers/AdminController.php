<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Cookie;


use App\ReportPost;
use App\ReportComment;
use App\User;
use App\Post;
use App\PostPage;
use App\PostUser;
use App\CommentU;
use App\CommentP;
use App\CommentPage;
use App\CommentUser;
use App\Page;
use App\LikePost;
use App\LikeComment;
use App\Message;

//viewModel
use App\DetailsReportViewModel;
use App\DetailsReportCommentViewModel;
use App\DetailsUserAdminViewModel;
use App\AdminDonutChartViewModel;

class AdminController extends Controller
{
  public function dashboard() {
    //ritorno le piu' recenti
    $report = ReportPost::latest()->get();
    $el_per_page = 5;
    $current_page_post = 1;
    $num_page_reportPost = intval(($report->count()/$el_per_page));
    if(($report->count() % $el_per_page) != 0){
     $num_page_reportPost++;
    }
    $reportList = $report->splice($current_page_post * $el_per_page - 5, 5);

    //segnalazioni commenti
    $reportComment = ReportComment::latest()->get();
    $el_per_page_comment = 5;
    $current_page_comment = 1;
    $num_page_reportComment = intval(($reportComment->count()/$el_per_page_comment));
    if(($reportComment->count() % $el_per_page_comment) != 0){
     $num_page_reportComment++;
    }
    $reportListComment = $reportComment->splice($current_page_comment * $el_per_page - 5, 5);

    //lista utenti
    $userList = User::latest()->get();
    $el_per_page_user = 5;
    $current_page_user = 1;
    $num_page_user = intval(($userList->count()/$el_per_page_user));
    if(($userList->count() % $el_per_page_comment) != 0){
     $num_page_user++;
    }
    $userList = $userList->splice($current_page_comment * $el_per_page - 5, 5);
    //recupero numero utenti totali
    $totUser = User::where('confirmed', '=', 1)->count();
    //recupero numero post totali
    $totPost = Post::count();
    //recupero numero commenti totali
    $totComment = CommentP::count();
    //recupero numero pagine totali
    $totPage = Page::count();

    //dati per i grafici
    //donut chart
    $users = User::all();
    $donutChart = array();
    $cnt = 0;
    foreach ($users as $u){
        $find = false;
        for($cnt = 0; $cnt < count($donutChart) && !$find; $cnt++) {
            if($donutChart[$cnt]->citta == $u->citta){
                $find = true;
            }
        }
        if(!$find){
            $tmp2 = new AdminDonutChartViewModel();
            $tmp2->citta = $u->citta;
            $tmp2->count = 1;
            array_push($donutChart, $tmp2);
        }else{
            for($cnt = 0; $cnt < count($donutChart); $cnt++){
                if($donutChart[$cnt]->citta == $u->citta){
                    $donutChart[$cnt]->count += 1;
                }
            }
        }
    }
    return view('/admin', compact('reportList', 'reportListComment', 'userList','totUser', 'totPost', 'totComment', 'totPage', 'num_page_reportPost', 'num_page_reportComment', 'num_page_user', 'donutChart'));
  }

  public function getPostDetails(Request $request){
    $id = $request->input('id_report');
    $report = ReportPost::where('id_report', '=', $id)->first();

    $post = Post::where('id_post', '=', $report->id_post)->first();


    $viewModel = new DetailsReportViewModel();
    $viewModel->content = $post->content;
    $viewModel->id_report = $report->id_report;

    $tmp = PostPage::where('id_post', '=', $post->id_post)->first();
    if(!$tmp){
        //devo cercare l'autore tra gli utenti
        $tmp = PostUser::where('id_post', '=', $post->id_post)->first();
        $author = User::where('id_user', '=', $tmp->id_user)->first();
        $viewModel->linkProfiloAutore = "/profile/user/" . $author->id_user;
        $viewModel->nomeAutore = $author->name . " " . $author->surname;
        $viewModel->tipoAutore = 1;
    }else{
        $author = Page::where('id_page', '=', $tmp->id_page)->first();
        $viewModel->linkProfiloAutore = "/page/" . $author->id_page;
        $viewModel->nomeAutore = $author->nome;
        $viewModel->tipoAutore = 2;
    }
    return response()->json($viewModel);


  }

  public function ignoreReportPost(Request $request){
    $id = $request->input('id_report');
    $report = ReportPost::where('id_report', '=', $id)->update(['status' => 'esaminata']);
    return response()->json(['message' => 'Operazione completata!']);
  }


  public function deletePost(Request $request){
    try{
        //recupero la notifica e la mrendo esaminata
        $id_report = $request->input('id_report');
        $report = ReportPost::where('id_report', '=', $id_report)->first();//update(['status' => 'esaminata']);
        $id_post = $report->id_post;

        $commentsParent = CommentU::where('id_post', '=', $id_post)->get();

        foreach ($commentsParent as $comment) {
            LikeComment::where('id_comment', '=', $comment->id_comment)->delete();
        }
        
        foreach ($commentsParent as $comment) {
            CommentUser::where('id_comment', '=', $comment->id_comment)->delete();
        }
        //$comments = CommentPage::where('id_post', '=', $id_post)->get();
        foreach ($commentsParent as $comment) {
            CommentPage::where('id_comment', '=', $comment->id_comment)->delete();
        }
         
        foreach($commentsParent as $comment) {
            ReportComment::where('id_comment', '=', $comment->id_comment)->delete();
         }
        foreach($commentsParent as $comment) {
            CommentU::where('id_comment', '=', $comment->id_comment)->delete();
            //CommentP::where('id_comment', '=', $comment->id_comment)->delete();
         }


        //elimino la notifica
        ReportPost::where('id_post', '=', intval($id_post))->delete();
        
        LikePost::where('id_post', '=', $id_post)->delete();

        $ban = $request->input('ban');
        if($ban == 1){
            //recupero l'utente o la pagina per bannarlo
            $postTmp = PostUser::where('id_post', '=', $id_post)->first();
            if(!$postTmp){
                $postTmp = PostPage::where('id_post', '=', $id_post)->first();
                $page = Page::where('id_page', '=', $postTmp->id_page)->update(['ban' => true]);
            }else{
                $user = User::where('id_user', '=', $postTmp->id_user)->update(['ban' => true]);
            }
        }

       

        PostUser::where('id_post', '=', $id_post)->delete();
        PostPage::where('id_post', '=', $id_post)->delete();




        Post::where('id_post', '=', $id_post)->delete();


        return response()->json(['message' => 'Operazione completata!']);

    }catch(Exception $e){
        return response()->json(['message' => $e->getMessage()]);
    }
    
  }


  public function listReportPost(Request $request){
    $page = $request->input('page');
    //$report = ReportPost::latest()->get();

    $filter = $request->input('filter');
    if(!$filter || $filter == "Tutte"){
        $report = ReportPost::latest()->get();
    }else{
        if($filter == "Aperte"){
            $report = ReportPost::where('status', '=', 'aperta')->latest()->get();
        }else{
            //esaminate
            $report = ReportPost::where('status', '=', 'esaminata')->latest()->get();
        }
    }

    
    $motivo = $request->input('motivo');
    if($motivo == "Incita all'odio"){
        $report = $report->filter(function ($value, $key) {
            return $value->description == 'Incita all\'odio';
        });
    }
    if($motivo == "È una notizia falsa"){
        $report = $report->filter(function ($value, $key) {
            return $value->description == 'È una notizia falsa';
        });
    }
    if($motivo == "È una minaccia"){
        $report = $report->filter(function ($value, $key) {
            return $value->description == 'È una minaccia';
        });
    }


    $id_report = intval($request->input('idReportPost'));
    if($id_report != -1){
        $c = collect();
        foreach ($report as $r) {
            if(strpos($r->id_report, (string)$id_report) || ($r->id_report == $id_report))
                $c->push($r);
        }
        $report = $c;  
    }

    


    $el_per_page = 5;
    $num_page_reportPost = intval(($report->count()/$el_per_page));
    if(($report->count() % $el_per_page) != 0){
     $num_page_reportPost++;
    }    $id_report = $request->input('id');
    


    $reportList = $report->splice($page * 5 - 5, 5);    


    $array = array();
    //$length = count($reportList);
    $x = 0;

    $el_per_page = 5;
    //$current_page_post = 1;

    
    foreach ($reportList as $report) {
        $post = Post::where('id_post', '=', $report->id_post)->first();
        $viewModel = new DetailsReportViewModel();
        $viewModel->content = $post->content;
        $viewModel->id_report = $report->id_report;

        $viewModel->description = $report->description;
        $viewModel->status = $report->status;

        $date = $report->created_at;

        $viewModel->created_at = $date->format('M j, Y H:i');

        $tmp = PostPage::where('id_post', '=', $post->id_post)->first();
        if(!$tmp){
            //devo cercare l'autore tra gli utenti
            $tmp = PostUser::where('id_post', '=', $post->id_post)->first();
            $author = User::where('id_user', '=', $tmp->id_user)->first();
            $viewModel->linkProfiloAutore = "/profile/user/" . $author->id_user;
            $viewModel->nomeAutore = $author->name . " " . $author->surname;
            $viewModel->tipoAutore = 1;
        }else{
            $author = Page::where('id_page', '=', $tmp->id_page)->first();
            $viewModel->linkProfiloAutore = "/profile/page/" . $author->id_page;
            $viewModel->nomeAutore = $author->nome;
            $viewModel->tipoAutore = 2;
        }
        $viewModel->totPage = $num_page_reportPost;
        $array[$x] = $viewModel;
        $x++;
    }

    
    return response()->json($array);
  }

  public function getCommentDetails(Request $request){
    $id = $request->input('id_report');
    $report = ReportComment::where('id_report', '=', $id)->first();

    $comment = CommentU::where('id_comment', '=', $report->id_comment)->first();


    $viewModel = new DetailsReportCommentViewModel();
    $viewModel->content = $comment->content;
    $viewModel->id_report = $report->id_report;

    $tmp = CommentPage::where('id_comment', '=', $comment->id_comment)->first();
    if(!$tmp){
        //devo cercare l'autore tra gli utenti
        $tmp = CommentUser::where('id_comment', '=', $comment->id_comment)->first();
        $author = User::where('id_user', '=', $tmp->id_user)->first();
        $viewModel->linkProfiloAutore = "/profile/user/" . $author->id_user;
        $viewModel->nomeAutore = $author->name . " " . $author->surname;
        $viewModel->tipoAutore = 1;
    }else{
        $author = Page::where('id_page', '=', $tmp->id_page)->first();
        $viewModel->linkProfiloAutore = "/page/" . $author->id_page;
        $viewModel->nomeAutore = $author->nome;
        $viewModel->tipoAutore = 2;
    }
    return response()->json($viewModel);


  }

  public function listReportComment(Request $request){
    $page = $request->input('page');
    //$report = ReportPost::latest()->get();

    $filter = $request->input('filter');
    if(!$filter || $filter == "Tutte"){
        $report = ReportComment::latest()->get();
    }else{
        if($filter == "Aperte"){
            $report = ReportComment::where('status', '=', 'aperta')->latest()->get();
        }else{
            //esaminate
            $report = ReportComment::where('status', '=', 'esaminata')->latest()->get();
        }
    }

    
    $motivo = $request->input('motivo');
    if($motivo == "Incita all'odio"){
        $report = $report->filter(function ($value, $key) {
            return $value->description == 'Incita all\'odio';
        });
    }
    if($motivo == "È una notizia falsa"){
        $report = $report->filter(function ($value, $key) {
            return $value->description == 'È una notizia falsa';
        });
    }
    if($motivo == "È una minaccia"){
        $report = $report->filter(function ($value, $key) {
            return $value->description == 'È una minaccia';
        });
    }


    $id_report = intval($request->input('idReportPost'));
    if($id_report != -1){
        $c = collect();
        foreach ($report as $r) {
            if(strpos($r->id_report, (string)$id_report) || ($r->id_report == $id_report))
                $c->push($r);
        }
        $report = $c;  
    }

    


    $el_per_page = 5;
    $num_page_reportPost = intval(($report->count()/$el_per_page));
    if(($report->count() % $el_per_page) != 0){
     $num_page_reportPost++;
    }    $id_report = $request->input('id');
    


    $reportList = $report->splice($page * 5 - 5, 5);    


    $array = array();
    //$length = count($reportList);
    $x = 0;

    $el_per_page = 5;
    //$current_page_post = 1;

    
    foreach ($reportList as $report) {
        $comment = CommentU::where('id_comment', '=', $report->id_comment)->first();
        $viewModel = new DetailsReportCommentViewModel();
        $viewModel->content = $comment->content;
        $viewModel->id_report = $report->id_report;

        $viewModel->description = $report->description;
        $viewModel->status = $report->status;

        $date = $report->created_at;

        $viewModel->created_at = $date->format('M j, Y H:i');

        $tmp = CommentPage::where('id_comment', '=', $comment->id_comment)->first();
        if(!$tmp){
            //devo cercare l'autore tra gli utenti
            $tmp = CommentUser::where('id_comment', '=', $comment->id_comment)->first();
            $author = User::where('id_user', '=', $tmp->id_user)->first();
            $viewModel->linkProfiloAutore = "/profile/user/" . $author->id_user;
            $viewModel->nomeAutore = $author->name . " " . $author->surname;
            $viewModel->tipoAutore = 1;
        }else{
            $author = Page::where('id_page', '=', $tmp->id_page)->first();
            $viewModel->linkProfiloAutore = "/page/" . $author->id_page;
            $viewModel->nomeAutore = $author->nome;
            $viewModel->tipoAutore = 2;
        }
        $viewModel->totPage = $num_page_reportPost;
        $array[$x] = $viewModel;
        $x++;
    }

    
    return response()->json($array);
  }

  public function ignoreReportComment(Request $request){
    $id = $request->input('id_report');
    $report = ReportComment::where('id_report', '=', $id)->update(['status' => 'esaminata']);
    return response()->json(['message' => 'Operazione completata!']);
  }


  public function deleteComment(Request $request){
    try{
        //recupero la notifica e la rendo esaminata
        $id_report = $request->input('id_report');
        $report = ReportComment::where('id_report', '=', $id_report)->first();//update(['status' => 'esaminata']);
        $id_comment = $report->id_comment;

        $comment = CommentU::where('id_comment', '=', $id_comment)->first();
        LikeComment::where('id_comment', '=', $comment->id_comment)->delete();
        
        

        //elimino la notifica
        ReportComment::where('id_comment', '=', intval($id_comment))->delete();
        

        $ban = $request->input('ban');
        if($ban == 1){
            //recupero l'utente o la pagina per bannarlo
            $postTmp = CommentUser::where('id_comment', '=', $id_comment)->first();
            if(!$postTmp){
                $postTmp = CommentPage::where('id_comment', '=', $id_comment)->first();
                $page = Page::where('id_page', '=', $postTmp->id_page)->update(['ban' => true]);
            }else{
                $user = User::where('id_user', '=', $postTmp->id_user)->update(['ban' => true]);
            }
        }

        CommentUser::where('id_comment', '=', $comment->id_comment)->delete();

        CommentPage::where('id_comment', '=', $comment->id_comment)->delete();

        $comment = CommentU::where('id_comment', '=', $id_comment)->delete();


        return response()->json(['message' => 'Operazione completata!']);

    }catch(Exception $e){
        return response()->json(['message' => $e->getMessage()]);
    }
    
  }



  public function listUser(Request $request){

    $page = $request->input('page');
    //$report = ReportPost::latest()->get();

    $filter = $request->input('filter');
    if(!$filter || $filter == "Tutti"){
        $users = User::latest()->get();
    }else{
        if($filter == "Bloccati"){
            $users = User::where('ban', '=', 1)->latest()->get();
        }else{
            $users = User::where('admin', '=', 1)->latest()->get();
        }
    }


    $id_user = intval($request->input('idUser'));
    if($id_user != -1){
        $c = collect();
        foreach ($users as $u) {
            if(strpos($u->id_user, (string)$id_user) || ($u->id_user == $id_user))
                $c->push($u);
        }
        $users = $c;  
    }


    $el_per_page = 5;
    $num_page_user = intval(($users->count()/$el_per_page));
    if(($users->count() % $el_per_page) != 0){
     $num_page_user++;
    }  
    


    $userList = $users->splice($page * 5 - 5, 5);    


    $array = array();
    //$length = count($reportList);
    $x = 0;

    //$current_page_post = 1;

    
    foreach ($userList as $u) {
        $viewModel = new DetailsUserAdminViewModel();
        $viewModel->id_user = $u->id_user;
        $viewModel->nome = $u->name . ' ' . $u->surname;
        $viewModel->ban = $u->ban;
        $viewModel->email = $u->email;
        $viewModel->created_at = $u->created_at->format('M j, Y H:i');
        $viewModel->admin = $u->admin;
        $viewModel->picPath = $u->pic_path;
        $viewModel->totPage = $num_page_user;
        $array[$x] = $viewModel;
        $x++;
    }

    
    return response()->json($array); 
  }

  public function getUserDetails(Request $request){
    $id = $request->input('id_user');
    $u = User::where('id_user', '=', $id)->first();

     $viewModel = new DetailsUserAdminViewModel();
     $viewModel->id_user = $u->id_user;
     $viewModel->nome = $u->name . ' ' . $u->surname;
     $viewModel->ban = $u->ban;
     $viewModel->email = $u->email;
     $viewModel->created_at = $u->created_at->format('M j, Y H:i');
     $viewModel->admin = $u->admin;
     $viewModel->picPath = '../' . $u->pic_path;
     $viewModel->totPage = 1;

    return response()->json($viewModel);


  }

  public function promuoviUser(Request $request){
    $id = $request->input('id_user');

    User::where([['id_user', '=', $id], ['admin', '=', false]])->update(['admin' => true]);

    return response()->json(['message' => 'Operazione completata!', 'body' => 'L\'utente è stato promosso a amministratore di UniBook.', 'classLabelAdd' => 'badge badge-primary', 'classLabelRemove' => '']);
  }

  public function retrocediUser(Request $request){
    $id = $request->input('id_user');

    User::where([['id_user', '=', $id], ['admin', '=', true]])->update(['admin' => false]);

    return response()->json(['message' => 'Operazione completata!', 'body' => 'L\'utente è stato retrocesso.', 'classLabelAdd' => '', 'classLabelRemove' => 'badge badge-primary']);
  }


  public function bloccaUser(Request $request){
    $id = $request->input('id_user');

    User::where([['id_user', '=', $id], ['ban', '=', false]])->update(['ban' => true]);

    return response()->json(['message' => 'Operazione completata!', 'body' => 'L\'utente è stato bloccato. Non potrà scrivere post e commentare su UniBook.', 'classLabelAdd' => 'badge badge-primary', 'classLabelRemove' => '']);
  }

  public function sbloccaUser(Request $request){
    $id = $request->input('id_user');

    User::where([['id_user', '=', $id], ['ban', '=', true]])->update(['ban' => false]);

    return response()->json(['message' => 'Operazione completata!', 'body' => 'L\'utente è stato sbloccato.', 'classLabelAdd' => 'badge badge-primary', 'classLabelRemove' => '']);
  }




  public function sendMessageUser(Request $request){
    if(Cookie::has('session')){
        $id_sender = Cookie::get('session');
        $id_receiver = $request->input('id_user');
        $text = $request->input('message');
        $newMessage = new Message();
        $newMessage->sender = $id_sender;
        $newMessage->receiver = $id_receiver;
        $newMessage->content = $text;
        $newMessage->letta = false;
        $newMessage->save();
        return response()->json(['message' => 'Operazione completata!', 'body' => 'Il messaggio è stato inviato con successo.']);
    }else{
        return response()->json(['message' => 'Errore!', 'body' => 'Non hai i permessi necessari.']);
    }
  }



}
