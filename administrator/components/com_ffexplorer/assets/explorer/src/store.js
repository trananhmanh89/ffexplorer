import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
    strict: true,

    state: {
        selectedPath: '',
        lockedFiles: [],
        history: [],
    },

    mutations: {
        selectPath(state, path) {
            state.selectedPath = path;
        },

        lock({lockedFiles}, path) {
            if (lockedFiles.indexOf(path) < 0) {
                lockedFiles.push(path);
            }
        },

        unlock({lockedFiles}, path) {
            const idx = lockedFiles.findIndex(item => item === path);

            if (idx !== -1) {
                lockedFiles.splice(idx, 1);
            }
        },

        setHistory({history}, path) {
            const idx = history.findIndex(item => item === path);

            if (idx !== -1) {
                history.splice(idx, 1);
            }

            history.push(path);
        },

        deleteHistory({history}, path) {
            const idx = history.findIndex(item => item === path);

            if (idx !== -1) {
                history.splice(idx, 1);
            }
        }
    },
});