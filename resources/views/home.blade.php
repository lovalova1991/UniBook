@extends('layouts.app')

@section('content')
  <article class="content">
  <div class="padding">
          <!-- content -->
              <!-- main col right -->
                  <div class="well">
                      <form class="form" method="POST" action="/post">
                          <h4>New Post</h4>
                          <div class="input-group text-center">
                            <input name="_token" type="hidden" value="{{ csrf_token() }}">
                              <input class="form-control input-lg" name="content" placeholder="Hey, What's Up?" type="text">
                              <span class="input-group-btn"><button type="submit" class="btn btn-lg btn-primary">Post</button></span>
                          </div>
                      </form>
                  </div>
                  <!--Pannello Post-->
                  <div class="panel panel-default" id="post">
                    <div class="panel-heading"><ul class="nav navbar-nav navbar-right">
                          <li class="dropdown">
                              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="glyphicon glyphicon-user"></i>
                              </a>
                              <ul class="dropdown-menu">
                                  <li><a href="">Dettagli Post</a></li>
                                  <li><a href="">Visualizza Profilo</a></li>
                                  <li><a href="">Rimuovi dagli amici</a></li>
                                  <li><a href="">Segnala Post</a></li>
                              </ul>
                          </li>
                      </ul>
                      <!--Post Header-->
                      <div>
                          <h4 id="post_u_name">User Name</h4>
                          <img id="post_pic_path" class="img-circle pull-left">
                      </div>
                    </div>
                    <!--Post Content-->
                    <div class="panel-body">
                    <p id="post_content">Content</p>
                    <div id="insert_after" class="clearfix"></div>
                    <!--Comment Panel-->
                    <hr>
                    <!--Comment Form-->
                    <form>
                    <div class="input-group">
                      <div class="input-group-btn">
                      <button id="like_butt" class="btn btn-default">+1</button><button class="btn btn-default"><i class="glyphicon glyphicon-share"></i></button>
                      </div>
                      <input class="form-control" placeholder="Add a comment.." type="text">
                    </div>
                    </form>
                    </div>
                 </div>
                <button id="load" onclick="loadOlder()" type="button"/>Load More..
          <hr>
  </div><!-- /padding -->
</article>
<aside class="side">Sidebar</aside>
</div>

  <script>

  function onLoad(data){
    $("#post").hide();
    //ciclo su tutti i post, al contrario perchè prendo dal più recente al più vecchio
    for(var i = data.length - 1; i >= 0; i--)
    {
      console.log(data.length);
      $post_clone = $("#post").clone();
      $post_clone.attr("id", "post_" + data[i].id_post);
      $post_clone.find("#post_u_name").text(data[i].auth_name + " " + data[i].auth_surname);
      $post_clone.find("#post_pic_path").attr('src', data[i].pic_path);
      $post_clone.find("#post_content").text(data[i].content);
      $post_clone.find("#like_butt").text(data[i].likes);
      $post_clone.find("#insert_after").attr('id', "insert_after" + data[i].id_post);
      $post_clone.insertAfter(".well");
      $post_clone.show();
      }
      data.forEach(function(el) {
        //carico i commenti
        if(el.comments.length > 0 && el.id_post == el.comments[0].id_post){
          for(j = 0; j < el.comments.length; j++){
            $("<div id='commpanel" + el.comments[j].id_comment +
               "'><hr><!--TODO: immagine-->" +
               "<a id='author' href='/profile/user/" + el.comments[j].id_user + "'>" +
               el.comments[j].auth_name + " " + el.comments[j].auth_surname +
               "</a><p id='comm_details'>" + el.comments[j].content +"</p></div>").insertAfter("#insert_after" + el.id_post);
      }
    }
  })
  }

  function loadOlder(){
    $prev_post = $("#post").prev();
    $post_id = $prev_post.attr("id").split("_")[1];
    $.ajax({
            method: "GET",
            url: "/home/loadmore",
            data: { post_id: $post_id },
            dataType : "json",
            success : function (posts)
            {
              if(posts.length != 0)
              {
                for(var x = posts.length - 1; x >= 0 ; x--)
                {
                  console.log(x);
                  $post_clone = $("#post").clone();
                  $post_clone.attr("id", "post_" + posts[x].id_post);
                  $post_clone.find("#post_u_name").text(posts[x].auth_name + " " + posts[x].auth_surname);
                  $post_clone.find("#post_pic_path").attr('src', posts[x].pic_path);
                  $post_clone.find("#post_content").text(posts[x].content);
                  $post_clone.find("#like_butt").text(posts[x].likes);
                  $post_clone.find("#insert_after").attr('id', "insert_after" + posts[x].id_post);
                  $post_clone.insertAfter("#post_" + $post_id);
                  $post_clone.show();
                  }
                  posts.forEach(function(el) {
                    //carico i commenti
                    if(el.comments.length > 0 && el.id_post == el.comments[0].id_post){
                      for(j = 0; j < el.comments.length; j++){
                        $("<div id='commpanel" + el.comments[j].id_comment +
                           "'><hr><!--TODO: immagine-->" +
                           "<a id='author' href='/profile/user/" + el.comments[j].id_user + "'>" +
                           el.comments[j].auth_name + " " + el.comments[j].auth_surname +
                           "</a><p id='comm_details'>" + el.comments[j].content +"</p></div>").insertAfter("#insert_after" + el.id_post);
                  }
                }
              })
              }
              else{
                $("#load").text("Nothing More!");
              }

            }
          })

  }

  //Caricamento dei post
  $(document).ready(function(){
       $.ajax({
           url : '/home/loadmore',
           method : "GET",
           dataType : "json",
           data: { post_id: -1 },
           success : function (posts)
           {
             onLoad(posts);
           }
       });
  });
  </script>
@endsection
