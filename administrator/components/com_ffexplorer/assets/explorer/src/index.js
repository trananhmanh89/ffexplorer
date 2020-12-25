import Vue from 'vue';
import App from './App.vue';
import store from './store';
import { 
    Select,
    Option,
    Input,
    Button,
    Message, 
    MessageBox,
    Loading,
    Dialog,
    Upload,
    Notification,
    Pagination,
} from "element-ui";
import lang from 'element-ui/lib/locale/lang/en';
import locale from 'element-ui/lib/locale';
import {translate} from './utils';

document.addEventListener('DOMContentLoaded', () => {
    __webpack_public_path__ = FF_EXPLORER_DATA.path.root + 'administrator/components/com_ffexplorer/assets/explorer/dist/';

    locale.use(lang)

    Vue.use(Select);
    Vue.use(Option);
    Vue.use(Input);
    Vue.use(Button);
    Vue.use(Dialog);
    Vue.use(Upload);
    Vue.use(Pagination);
    Vue.use(Loading.directive);

    Vue.prototype.$loading = Loading.service;
    Vue.prototype.$msgbox = MessageBox;
    Vue.prototype.$alert = MessageBox.alert;
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$prompt = MessageBox.prompt;
    Vue.prototype.$message = Message;
    Vue.prototype.$notify = Notification;

    Vue.prototype.$t = function(text) {
        return translate(text);
    }

    Vue.prototype.$ajax = function(options, method) {
        const $ = jQuery;
        const {path, params} = FF_EXPLORER_DATA;
        const url = path.ajax;
    
        return new Promise((resolve, reject) => {
            $.ajax({
                method: method ? method : 'post',
                url: url,
                dataType: 'json',
                data: $.extend({}, params, options),
            })
            .done(response => {
                resolve(response);
            })
            .fail(error => {
                reject(error);
            });
        });
    }

    new Vue({
        store,
        render: h => h(App)
    }).$mount('#explorer-app');
});