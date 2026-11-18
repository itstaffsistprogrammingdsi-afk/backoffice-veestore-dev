const APP_ROOT_ID = 'app';

export const lockAppRoot = () => {
    const appRoot = document.getElementById(APP_ROOT_ID);

    if (!appRoot) return;

    const count = Number(appRoot.dataset.modalOpenCount ?? 0) + 1;

    appRoot.dataset.modalOpenCount = String(count);

    if (count === 1) {
        appRoot.setAttribute('inert', '');
    }
};

export const unlockAppRoot = () => {
    const appRoot = document.getElementById(APP_ROOT_ID);

    if (!appRoot) return;

    const count = Math.max(0, Number(appRoot.dataset.modalOpenCount ?? 0) - 1);

    if (count === 0) {
        appRoot.removeAttribute('inert');
        delete appRoot.dataset.modalOpenCount;
        return;
    }

    appRoot.dataset.modalOpenCount = String(count);
};
