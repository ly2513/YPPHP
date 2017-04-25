/*
 * 调试工具栏.
 */

var ciDebugBar = {

    toolbar: null,

    // 初始化需要加载的函数
    init: function () {
        this.toolbar = document.getElementById('debug-bar');

        ciDebugBar.createListeners();
        ciDebugBar.setToolbarState();
        ciDebugBar.setIcoTop();
    },

    // 创建监听事件
    createListeners: function () {
        var buttons = [].slice.call(document.querySelectorAll('#debug-bar .ci-label a'));

        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener('click', ciDebugBar.showTab, true);
        }
    },

    // 显示tab
    showTab: function () {
        // 获取目标tab
        var tab = this.getAttribute('data-tab');

        // 获取当前tab的显隐性
        var state = document.getElementById(tab).style.display;

        if (tab == undefined) return true;

        // 隐藏所有的tab
        var tabs = document.querySelectorAll('#debug-bar .tab');

        for (var i = 0; i < tabs.length; i++) {
            tabs[i].style.display = 'none';
        }

        // 标记所有标签为无效
        var labels = document.querySelectorAll('#debug-bar .ci-label');

        for (var i = 0; i < labels.length; i++) {
            ciDebugBar.removeClass(labels[i], 'active');
        }

        // 显示/隐藏选中的tab
        if (state != 'block') {
            document.getElementById(tab).style.display = 'block';
            ciDebugBar.addClass(this.parentNode, 'active');
        }
    },

    // 添加class
    addClass: function (el, className) {
        if (el.classList) {
            el.classList.add(className);
        }
        else {
            el.className += ' ' + className;
        }
    },

    // 移除class
    removeClass: function (el, className) {
        if (el.classList) {
            el.classList.remove(className);
        }
        else {
            el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
        }

    },

    /**
     * 切换数据表的显示
     * @param obj
     */
    toggleDataTable: function (obj) {
        if (typeof obj == 'string') {
            obj = document.getElementById(obj + '_table');
        }

        if (obj) {
            obj.style.display = obj.style.display == 'none' ? 'block' : 'none';
        }
    },

    // 切换工具条到图标
    toggleToolbar: function () {
        var ciIcon = document.getElementById('debug-icon');
        var ciBar = document.getElementById('debug-bar');
        var open = ciBar.style.display != 'none';

        ciIcon.style.display = open == true ? 'inline-block' : 'none';
        ciBar.style.display = open == false ? 'inline-block' : 'none';

        // 记住这个网站的其他页面加载
        ciDebugBar.createCookie('debug-bar-state', '', -1);
        ciDebugBar.createCookie('debug-bar-state', open == true ? 'minimized' : 'open', 365);
    },

    // 设置页面的初始状态（打开或最小化）
    setToolbarState: function () {
        var open = ciDebugBar.readCookie('debug-bar-state');
        var ciIcon = document.getElementById('debug-icon');
        var ciBar = document.getElementById('debug-bar');

        ciIcon.style.display = open != 'open' ? 'inline-block' : 'none';
        ciBar.style.display = open == 'open' ? 'inline-block' : 'none';
    },

    /**
     * 创建Cookie
     *
     * @param name
     * @param value
     * @param days
     */
    createCookie: function (name, value, days) {
        if (days) {
            var date = new Date();

            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));

            var expires = "; expires=" + date.toGMTString();
        }
        else {
            var expires = "";
        }

        document.cookie = name + "=" + value + expires + "; path=/";
    },


    // 读取cookie
    readCookie: function (name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');

        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1, c.length);
            }
            if (c.indexOf(nameEQ) == 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    },
    // 修改图表的top值
    setIcoTop: function () {
        var top = window.screen.availHeight;

        document.getElementById('debug-icon').style.top = top / 2 + 'px';

    }


};
