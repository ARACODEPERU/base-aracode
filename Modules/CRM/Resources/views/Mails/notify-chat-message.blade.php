<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Notificación de mensaje</title>
</head>
@php
    //dd($data)
@endphp
<body>
   <h4>El estudiante {{ $data['fullName'] }}</h4>
   <p>Hizo una consulta</p>
   <br />
   <p>{!! $data['message'] !!}</p>
</body>
</html>
