(async function(){
    console.log('ok is ok');
    await import('./htmx-lite.js');
    console.log('htmx imported');
    let fd=new FormData();
    hxl_send_form('tracker.php/home',fd,document.body); 
    let registration = await navigator.serviceWorker.register("service.js");
    result = await Notification.requestPermission();
    console.log("Notification permission:", result);
    await navigator.serviceWorker.ready;
    if (registration.active) {
      console.log("Service worker is active");
    } else {
      console.log("Service worker not active");
    }
    window.addEventListener("beforeinstallprompt", (event) => {
        console.log("Before install prompt");
        let installPrompt = event;
        const installButton = document.querySelector("#install");
        installButton.classList.remove("hidden");
        installButton.addEventListener("click", async () => {
          const result = await installPrompt.prompt();
          console.log(`Install prompt was: ${result.outcome}`);
          installPrompt = null;
          installButton.classList.add("hidden");
          console.log("Install button clicked");
        });
      }); 
})();

window.getLocation=function() {
    const options = {
        enableHighAccuracy: true,
        timeout: 30000,
        maximumAge: 0,
    };
    return new Promise((resolve, reject) => {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                position => {
                    resolve({
                        coords:position.coords
                    });
                },
                error => {
                    reject(new Error('Error occurred: ' + error.message));
                },
                options
            );
        } else {
            reject(new Error('Geolocation is not supported by this browser.'));
        }
    });
}

window.register_event= async function(event) {
    let form = event.target.form ?? event.target.closest('form');
    let formData = new FormData(form);
    if (event.target.hasAttribute('id')) {
        formData.append('id', event.target.getAttribute('id'));
    } else {
        return;
    }
    event.target.classList.add('requested');
    event.target.classList.remove('failed');
    try{
        const location = await getLocation();
        formData.append('latitude', location.coords.latitude);
        formData.append('longitude', location.coords.longitude);
    }catch(error){
        formData.append('error',error);
    }
    let action = form.action;
    hxl_send_form(action, formData, event.target);
}

window.more_callback= function(entries, observer){
    console.log('calling for more');
    let entry=entries[0];
    if(! entry.isIntersecting)
        return;
    observer.disconnect();
    let target=entry.target;
    let action=target.getAttribute('action');
    console.log('loading more...');
    let formData=new FormData();
    hxl_send_form(action,formData,target);
}

window.watch4more=function(){
    const sentinel= document.getElementById('sentinel');
    if(sentinel != null){
        const observer = new IntersectionObserver(more_callback);
        observer.observe(sentinel);
    }else{
        console.log('no sentinel found');
    }
}
