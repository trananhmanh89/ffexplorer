import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
    strict: true,

    state: {
        app: '',
        selectedPath: '',
        lockedFiles: [],
        history: [],
        db_keyword: '',
        activeTable: '',
    },

    actions: {
        setApp({state, commit}, payload) {
            commit('setApp', payload);
        }
    },

    mutations: {
        setActiveTable(state, value) {
            state.activeTable = value;
        },

        searchDb(state, keyword) {
            state.db_keyword = keyword;
        },

        setApp(state, app) {
            state.app = app;
        },

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