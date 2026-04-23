<h2>Nouveau message depuis le site RHS</h2>

<p><strong>Nom :</strong> {{ $data['name'] }}</p>
<p><strong>Email :</strong> {{ $data['email'] }}</p>
<p><strong>Téléphone :</strong> {{ $data['phone'] ?? '—' }}</p>
<p><strong>Objet :</strong> {{ $data['subject'] }}</p>

<hr>

<p><strong>Message :</strong></p>
<p>{!! nl2br(e($data['message'])) !!}</p>
