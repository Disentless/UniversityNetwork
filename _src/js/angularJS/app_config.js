// Путь к моделям маршрутизатора.
var viewFolder = 'ui.router/views/';

var app = angular.module("websiteApp", ['ui.router', 'ngMaterial', 'ngMessages', 'ngAnimate'])
    // Настройка маршрутизатора
    .config(['$stateProvider', '$urlRouterProvider', function($stateProvider, $urlRouterProvider) {
        $urlRouterProvider.otherwise('/main');

        $stateProvider
        // Корень
        .state('app',{
            abstract: true,
            url: '/',
            views: {
                'header' : {
                    templateUrl: viewFolder + 'header.html',
                    controller: 'headerController'
                },
                'body' : {},
                'footer' : {
                    templateUrl: viewFolder + 'footer.html'
                }
            }
        })
        .state('app.main', {
            url: 'main',
            views: {
                'main' : {
                    templateUrl: viewFolder + 'main.html',
                    controller: 'mainController'
                }
            }
        })
        // Состояние отображения страницы входа. Внутри ui-view="body".
        .state('app.main.login',{
            url: '/login',
            views: {
                'login' : {
                    templateUrl: viewFolder + 'login.html',
                    controller: 'loginController'
                }
            }
        })
        // Состояние отображения страницы регистрации. Внутри ui-view="body".
        .state('app.main.register',{
            url: '/register',
            views: {
                'register' : {
                    templateUrl: viewFolder + 'register.html',
                    controller: 'registerController'
                }
            }
        })
        // Кабинеты
        .state('app.admin',{
            url: 'admin',
            views: {
                'adminPC' : {
                    templateUrl: viewFolder + 'admin.html',
                    controller: 'adminController'
                }
            }
        })
        .state('app.manager',{
            url: 'manager',
            views: {
                'managerPC' : {
                    templateUrl: viewFolder + 'manager.html',
                    controller: 'managerController'
                }
            }
        })
        .state('app.student',{
            url: 'student',
            views: {
                'studentPC' : {
                    templateUrl: viewFolder + 'student.html'
                }
            }
        })
        .state('app.student.profile',{
            url: '/profile',
            views: {
                'profile' : {
                    templateUrl: viewFolder + 'student_profile.html',
                    controller: 'studentProfileController'
                }
            }
        })
        .state('app.student.news',{
            url: '/news',
            views: {
                'news' : {
                    templateUrl: viewFolder + 'student_news.html'
                }
            }
        })
        .state('app.student.group',{
            url: '/group',
            views: {
                'group' : {
                    templateUrl: viewFolder + 'student_group.html'
                }
            }
        })
        .state('app.student.schedule', {
            url: '/schedule',
            views: {
                'schedule' : {
                    templateUrl: viewFolder + 'student_schedule.html',
                    controller: 'studentScheduleController'
                }
            }
        })
        .state('app.student.subjects', {
            url: '/subjects',
            views: {
                'subjects' : {
                    templateUrl: viewFolder + 'student_subjects.html',
                    controller: 'studentSubjectsController'
                }
            }
        })
    }])
    .config(['$mdDateLocaleProvider', function($locale){
        $locale.firstDayOfWeek = 1;
    }])
    .config(['$mdThemingProvider', function($mdThemingProvider){
        $mdThemingProvider.theme('default')
            .primaryPalette('blue')
            .accentPalette('red')
            .backgroundPalette('grey');
    }]);
app.factory('httpInjector', [function(){
    return {
        request: function(config){
            config.requestTimestamp = new Date().getTime();
            return config;
        },
        response: function(response){
            response.config.responseTimestamp = new Date().getTime();
            response.config.requestTime = response.config.responseTimestamp - response.config.requestTimestamp;
            return response;
        }
    };
}]);
app.config(['$httpProvider', function($httpProvider){
    $httpProvider.interceptors.push('httpInjector');
}]);