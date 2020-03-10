import Vue from 'vue';
import App from './App.vue';
import store from './store';

import { 
    Button,
    Message, 
    MessageBox,
    Loading,
    Dialog,
    Upload,
} from "element-ui";
import lang from 'element-ui/lib/locale/lang/en';
import locale from 'element-ui/lib/locale';


document.addEventListener('DOMContentLoaded', () => {
    locale.use(lang)

    Vue.use(Button);
    Vue.use(Dialog);
    Vue.use(Upload);
    Vue.use(Loading.directive);

    Vue.prototype.$loading = Loading.service;
    Vue.prototype.$msgbox = MessageBox;
    Vue.prototype.$alert = MessageBox.alert;
    Vue.prototype.$confirm = MessageBox.confirm;
    Vue.prototype.$prompt = MessageBox.prompt;
    Vue.prototype.$message = Message;

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