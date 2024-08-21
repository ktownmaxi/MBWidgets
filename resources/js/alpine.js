import folderTree from './components/folder-tree';
import setupEditor from './components/tiptap';
import workTime from './components/work-time.js';
import notifications from './components/wireui/notifications';
import signature from './components/signature-pad.js';
import addressMap from "./components/address-map";

window.folderTree = folderTree;
window.setupEditor = setupEditor;
window.workTime = workTime;
window.addressMap = addressMap;
window.signature = signature;

window.addEventListener('alpine:init', () => {
    window.Alpine.data('wireui_notifications', notifications);
})

Alpine.directive('currency', (el, { expression }, { evaluate }) => {
    const data = evaluate(expression);

    el.innerText = formatters.money(data.value, data.currency);
});

Alpine.directive('percentage', (el, { expression }, { evaluate }) => {
    el.innerText = formatters.percentage(evaluate(expression));
})

document.addEventListener(
    'livewire:navigated',
    function() {
        wireNavigation();
    },
    {once: true}
);

document.addEventListener('livewire:init', () => {
    wireNavigation();

    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                window.location.reload();

                preventDefault();
            }
        })
    })
})

function wireNavigation() {
    let links = [...document.querySelectorAll('a[href]')].filter(link => {
        let hrefValue = link.getAttribute('href').trim();
        return hrefValue !== '' && hrefValue !== '#' && (hrefValue.startsWith(window.location.origin) || hrefValue.startsWith('/'));
    });

    links.forEach(link => {
        link.setAttribute('wire:navigate', 'true');
    });
}

Livewire.directive('flux-confirm', ({ el, directive }) => {
    let icon = directive.modifiers.includes('icon')
        ? directive.modifiers[directive.modifiers.indexOf('icon') + 1]
        : 'question';

    let id = directive.modifiers.includes('prompt')
        ? 'prompt'
        : (directive.modifiers.includes('id') ? directive.modifiers[directive.modifiers.indexOf('id') + 1] : null);

    // Convert sanitized linebreaks ("\n") to real line breaks...
    let message = directive.expression.replaceAll('\\n', '\n').split('|');
    let title = message.shift();
    let description = message[0];
    let cancelLabel = message[1] ?? 'Cancel';
    let confirmLabel = message[2] ?? 'Confirm';

    if (title === '') title = 'Are you sure?';

    el.__livewire_confirm = (action) => {
        window.$wireui.confirmDialog({
            id: id,
            title: title,
            description: description,
            icon: icon,
            accept: {
                label: confirmLabel,
                method: null,
                execute: () => {
                    action();
                }
            },
            reject: {
                label: cancelLabel,
                method: 'cancel'
            }
        });
    }
})

window.$promptValue = (id) => {
    const el = document.getElementById(id ? id : 'prompt-value');

    if (el.type === 'checkbox') {
        return el.checked;
    }

    return el.value;
}
