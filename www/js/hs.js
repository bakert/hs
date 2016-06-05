function preventFocus() { isTop = false; }
function focusForm()    { if (isTop && document.f) { document.f.q.select(); } }
var isTop       = true;
window.onscroll = preventFocus;
window.onload   = focusForm;
