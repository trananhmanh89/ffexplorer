import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
    strict: true,

    state: {
        selectedPath: '',
    },

    actions: {

    },

    mutations: {
        selectPath(state, path) {
            state.selectedPath = path;
        },
    },
});