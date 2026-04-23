<div id="rhs-help-root">

  <button id="rhs-help-fab" class="rhs-fab">
    <span class="rhs-fab__circle">
      <img src="{{ asset('images/help-agent.png') }}" class="rhs-fab__avatar" alt="">
    </span>
    <span class="rhs-fab__label">+ D'INFO</span>
  </button>

  <div id="rhs-help-bar" class="rhs-infobar" aria-hidden="true">
    <div class="rhs-infobar__inner">

      <div class="rhs-infobar__left">
        <img src="{{ asset('images/help-agent.png') }}" class="rhs-infobar__avatar" alt="">
        <div class="rhs-infobar__text">
          <h3>Comment pouvons-nous vous aider ?</h3>
          <p>Sélectionnez une action ci-dessous</p>
        </div>
      </div>

      <div class="rhs-infobar__actions">
        <a href="{{ route('apply') }}" class="rhs-pill pill-red">Postuler</a>
        <a href="{{ route('services') }}" class="rhs-pill pill-blue">Recruter</a>
        <a href="{{ route('services') }}" class="rhs-pill pill-green">Services</a>
        <a href="{{ route('contact') }}" class="rhs-pill pill-orange">Contact</a>
      </div>

      <button id="rhs-help-close" class="rhs-infobar__close">✕</button>
    </div>
  </div>

</div>
