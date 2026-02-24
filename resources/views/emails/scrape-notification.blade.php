<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>スクレイプ通知</title>
</head>
<body>
@foreach ($matched as $web => $posts)
「{{ $web }}」でマッチ<br>

@foreach ($posts as $post)
@if(!empty($post['code']))
【コード】<br>{{ $post['code'] }}<br>
@endif
@if(!empty($post['company_name']))
【会社名】<br>{{ $post['company_name'] }}<br>
@endif
【タイトル】<br>「{{ $post['text'] }}」<br>
【URL】<br><a href="{{ $post['href'] }}">{{ $post['href'] }}</a><br>
@endforeach
- - - - - - - - - - - -<br>
@endforeach
</body>
</html>
