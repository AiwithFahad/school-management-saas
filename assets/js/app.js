// EduPro — theme (light/dark, [data-theme] + legacy body.dark), sidebar collapse, confirm dialogs. Presentation only.
(function(){
  function applyTheme(t){ document.documentElement.setAttribute('data-theme',t); document.body.classList.toggle('dark',t==='dark');
    var b=document.getElementById('themeToggle'); if(b) b.innerHTML='<i class="bi bi-'+(t==='dark'?'sun':'moon')+'"></i>'; }
  var saved='light'; try{ saved=localStorage.getItem('edu-theme')||'light'; }catch(e){}
  applyTheme(saved);
  document.getElementById('themeToggle')?.addEventListener('click',function(){ var t=document.body.classList.contains('dark')?'light':'dark'; applyTheme(t); try{ localStorage.setItem('edu-theme',t); }catch(e){} });
  try{ if(localStorage.getItem('edu-sidebar')==='collapsed') document.body.classList.add('sidebar-collapsed'); }catch(e){}
  document.getElementById('sidebarToggle')?.addEventListener('click',function(){ document.body.classList.toggle('sidebar-collapsed'); try{ localStorage.setItem('edu-sidebar',document.body.classList.contains('sidebar-collapsed')?'collapsed':'open'); }catch(e){} });
})();
document.querySelectorAll('[data-confirm]')?.forEach(el=>el.addEventListener('click',ev=>{if(!confirm(el.dataset.confirm))ev.preventDefault();}));
