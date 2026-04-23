<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmation de réception</title>
</head>

<body style="margin:0; padding:0; background:#f6f7fb; font-family:Arial, sans-serif;">
  <div style="max-width:640px; margin:0 auto; padding:24px;">

    {{-- LOGO HEADER --}}
    <div style="text-align:center; margin-bottom:14px;">
      <img
        src="{{ asset('images/rhs-logo.png') }}"
        alt="RHS GROUP"
        style="max-width:160px; height:auto; display:inline-block;"
      >
    </div>

    <div style="background:#ffffff; border-radius:16px; padding:22px; border:1px solid rgba(15,23,42,.08);">

      <h2 style="margin:0 0 10px; color:#0f172a;">
        Bonjour {{ $application->full_name }},
      </h2>

      <p style="margin:0 0 12px; color:#334155; line-height:1.6;">
        Nous confirmons la bonne réception de votre candidature par <strong>RHS GROUP</strong>.
      </p>

      <div style="background:#f8fafc; border:1px solid rgba(15,23,42,.08); padding:14px; border-radius:14px; margin:16px 0;">
        <p style="margin:0; color:#0f172a;">
          <strong>Poste :</strong>
{{ $application->offer->title ?? $application->position ?? 'Candidature' }}


        </p>

        <p style="margin:8px 0 0; color:#0f172a;">
          <strong>Type :</strong>
          {{ $application->type === 'spontaneous' ? 'Candidature spontanée' : 'Candidature à une offre' }}
        </p>

        @if(!empty($application->city))
          <p style="margin:8px 0 0; color:#0f172a;">
            <strong>Ville :</strong> {{ $application->city }}
          </p>
        @endif
      </div>

      <p style="margin:0 0 12px; color:#334155; line-height:1.6;">
        Notre équipe RH étudiera votre dossier avec attention. Si votre profil correspond à nos besoins,
        nous vous contacterons prochainement.
      </p>

      <p style="margin:0; color:#64748b; font-size:13px; line-height:1.6;">
        Cet email est automatique, merci de ne pas y répondre.
      </p>

      <div style="margin-top:18px; padding-top:14px; border-top:1px solid rgba(15,23,42,.08);">
        <p style="margin:0; color:#0f172a;">
          Cordialement,<br>
          <strong>RHS GROUP</strong><br>
          <span style="color:#64748b;">contact@rhsgroup.ma</span>
        </p>
      </div>
    </div>

    <p style="text-align:center; margin:14px 0 0; color:#94a3b8; font-size:12px;">
      © {{ date('Y') }} RHS GROUP
    </p>

  </div>
</body>
</html>
