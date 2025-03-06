const reqested_class = 'requested';
const failed_class = 'failed';
const showErrorId = 'show_error';

/**
 * function can be used in onclick within a form element
 * @param {*} event 
 */
globalThis.hxl_submit_form = function (event) {
    event.target.classList.add(reqested_class);
    event.target.classList.remove(failed_class);
    let form = event.target.form ?? event.target.closest('form');
    let action = form.action;
    let formData = new FormData(form);
    if (event.target.hasAttribute('name')) {
        formData.append('name', event.target.getAttribute('name'));
    }
    hxl_send_form(action, formData, event.target);
};

/**
 * executing a fetch
 * @param {*} action the url/path to send to
 * @param {*} formData  data to send as as form encoded in post body
 * @param {*} target element related to this action
 */
globalThis.hxl_send_form = async function (action, formData, target) {
    try {
        let response = await fetch(action, { method: "POST", body: formData });
        if (response.status == 200) {
            target.classList.remove(reqested_class);
            target.classList.remove(failed_class);
            let data = await response.text();
            if (data.trim().length > 0) {
                hxl_process_body(data);
            } else {
                console.log('empty body returned');
            }
        } else {
            target.classList.remove(reqested_class);
            target.classList.add(failed_class);
            const activeDialog = document.querySelector('dialog[open]');
            if (activeDialog) {
                activeDialog.close();
            }
            let se = document.getElementById(showErrorId);
            se.innerHTML = 'showing errors';
            let text = await response.text();
            se.innerHTML = text;
            for (const s of se.getElementsByTagName('style')) {
                s.remove();
            }
            se.showModal();
            target?.Focus?.();
        }
    }
    catch (error) {
        console.log(error);
        let se = document.getElementById(showErrorId);
        se.innerHTML = `${error.name}: ${error.message}`;
        se.show();
    }
};

/**
 * the returned text is html with additional tags
 * @param {*} responseText 
 */
globalThis.hxl_process_body = function (responseText) {
    // create an unattached element
    const div = document.createElement('div');
    // parses html but also drops tags that are not proper in this structure like tr without a table-parent
    div.innerHTML = responseText;
    // go through those with actions
    for (const n of div.querySelectorAll("[x-action]")) {
        let attr = n.getAttribute('x-action');
        if (n.id) {
            // for replacements/deletes
            var sameId = document.getElementById(n.id);
        }
        var oid = '';
        if (n.hasAttribute('x-id')) {
            // for related elements
            oid = n.getAttribute('x-id');
            var otherId = document.getElementById(oid);
        }
        switch (attr) {
            case 'after':
                otherId.after(n);
                break;
            case 'append':
                otherId.append(n);
                break;
            case 'before':
                otherId.before(n);
                break;
            case 'prepend':
                otherId.prepend(n);
                break;
            case 'remove':
                if (sameId != null)
                    sameId.remove();
                else
                    console.log(`element ${n.id} was not found`);
                break;
            case 'replace':
                if (sameId != null)
                    sameId.replaceWith(n);
                else
                    if (otherId != null)
                        otherId.append(n);
                    else
                        if (oid == 'head')
                            document.head.appendChild(n);
                        else
                            document.body.appendChild(n);
                break;
            default:
                console.log('had no action defined for ', n);
        }
        if (n.tagName == 'DIALOG' && attr != 'remove') {
            n.showModal();
        }
    }
    // scripts are not automatically executed
    for (const n of div.getElementsByTagName('script')) {
        eval(n.innerText);
    }
}

let seDialog = document.getElementById(showErrorId);
if (seDialog == undefined) {
    seDialog = document.createElement('dialog');
    seDialog.id = showErrorId;
    seDialog.onclick = function () {
        seDialog.close();
        seDialog.innerHTML = '';
    };
    document.body.append(seDialog);
}
