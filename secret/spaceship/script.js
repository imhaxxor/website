function h(evt){evt.preventDefault()
window.removeEventListener('touchstart',h,null)
window.removeEventListener('click',h,null)
const song=new Audio('song.mp3');document.querySelector('.before').remove();document.querySelector('.after').style.display='block';song.play();}
window.addEventListener('touchstart',h)
window.addEventListener('click',h)