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
            let app = '';
            if (payload) {
                app = payload === 'database' ? 'database' : 'explorer';
            } else {
                const {hash} = location;
                const query = hash.replace('#/', '');
                app = query === 'database' ? 'database' : 'explorer';
            }

            if (app !== state.app) {
                jQuery(window).off('resize.ffexplorer');
            }

            location.hash = '#/' + app;
            commit('setApp', app);
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
            state.selectedPath = '';
            state.db_keyword = '';
            state.activeTable = '';
            Vue.set(state, 'lockedFiles', []);
            Vue.set(state, 'history', []);
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