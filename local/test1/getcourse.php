<!DOCTYPE html>
<html ng-app="moodleApp">
<head>
    <title>Course List</title>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
    <script>
        var app = angular.module('moodleApp', []);
        app.controller('CourseController', function ($scope, $http) {
            var apiUrl = 'http://localhost/moodle4/webservice/rest/server.php';
            var params = {
                wstoken: 'b74c8b9b81e56c24a03329b76137cc98',
                wsfunction: 'core_course_get_courses',
                moodlewsrestformat: 'json'
            };
            $http({
                method: 'GET',
                url: apiUrl,
                params: params
            }).then(function (response) {
                $scope.courses = response.data.reverse();
                $scope.totalcourse = $scope.courses.length;
                $scope.pageSize = 10;
                $scope.currentPage = 1;

                $scope.paginatedCourses = function () {
                    var start = ($scope.currentPage - 1) * $scope.pageSize;
                    var end = start + $scope.pageSize;
                    return $scope.courses.slice(start, end);
                };

                $scope.nextPage = function () {
                    if ($scope.currentPage < $scope.totalPages()) {
                        $scope.currentPage++;
                    }
                };

                $scope.prevPage = function () {
                    if ($scope.currentPage > 1) {
                        $scope.currentPage--;
                    }
                };

                $scope.goToPage = function (page) {
                    if (page >= 1 && page <= $scope.totalPages()) {
                        $scope.currentPage = page;
                    }
                };

                $scope.totalPages = function () {
                    return Math.ceil($scope.totalcourse / $scope.pageSize);
                };

            }, function (error) {
                console.error('Error fetching courses:', error);
                $scope.error = 'Could not fetch courses';
            });
        });


        app.controller('MessageController', function($scope) {
            $scope.message = "Hello, JS!";

            function generateRandomString(length) {
                var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var result = '';
                for (var i = 0; i < length; i++) {
                    var randomIndex = Math.floor(Math.random() * characters.length);
                    result += characters[randomIndex];
                }
                return result;
            }
            $scope.changeMessage = function() {
                $scope.message = generateRandomString(10);
                window.console.log('String created... '+ $scope.message)
            };
        });
    </script>
</head>

<?php

require_once('../../config.php');
require_login();

$context = context_system::instance();
$url = new moodle_url( '/local/test1/getcourse.php' );

$PAGE->set_url( $url );
$PAGE->set_context( $context );
$PAGE->set_title( 'Course List' );

$courseurl = new moodle_url( "/course/view.php" );

echo $OUTPUT->header();
echo '<div ng-controller="MessageController" class="d-none">
    <h4>{{ message }}</h4>
    <button ng-click="changeMessage()" class="btn btn-info ">Change Message</button>
</div>';
echo '<body ng-controller="CourseController">
        <div class="d-flex justify-content-between">
            <h4>MDL - 4 : Courses by js</h4>
            <span class="float-right mt-1"><strong> Total course :- {{ totalcourse }} </strong></span>
        </div>   
        <div ng-if="!courses">
            Loading courses...
        </div>
        <div ng-if="error">
            {{ error }}
        </div>
        <div ng-if="courses">
            <table class="table table-striped">
                <tr>
                    <th>id</th>
                    <th>Course Name</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                <tr ng-repeat="course in paginatedCourses()">
                    <td>{{ course.id }}</td>
                    <td><a href="{{courseurl}}?id={{course.id}}" target="_blank" class="text-decoration-none text-body">{{ course.shortname }}</a></td>
                    <td>{{ course.timecreated * 1000 | date }}</td>
                    <td><a href="{{courseurl}}?id={{course.id}}" target="_blank" class="btn btn-primary">View</a></td>
                </tr>
            </table>
            <div>
                <button ng-click="prevPage()" ng-disabled="currentPage == 1" class="btn btn-primary">Previous</button>
                <span>Page {{ currentPage }} of {{ totalPages() }}</span>
                <button ng-click="nextPage()" ng-disabled="currentPage == totalPages()" class="btn btn-primary">Next</button>
            </div>
        </div>
    </body>
</html>';
echo $OUTPUT->footer();
?>