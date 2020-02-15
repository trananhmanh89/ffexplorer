import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
    strict: true,

    state: {
        selectedPath: '',
        lockedFiles: [],
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

            if (idx > -1) {
                lockedFiles.splice(idx, 1);
            }
        }
    },
});