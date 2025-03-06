(async function(){
    console.log('ok is ok');
    await import('./htmx-lite.js');
    console.log('htmx imported');
    let fd=new FormData();
    hxl_send_form('tracker.php/main',fd,document.body); 
})();