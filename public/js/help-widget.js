(function(){
  const fab = document.getElementById('rhs-help-fab');
  const bar = document.getElementById('rhs-help-bar');
  const close = document.getElementById('rhs-help-close');

  if(!fab || !bar || !close) return;

  fab.onclick = () => {
    bar.classList.toggle('open');
  };

  close.onclick = () => {
    bar.classList.remove('open');
  };

  window.addEventListener('keydown', e => {
    if(e.key === 'Escape') bar.classList.remove('open');
  });
})();
