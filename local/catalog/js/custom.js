 
 //pagingCtrl
//var myModule = angular.module('hello', ['angularUtils.directives.dirPagination']);
  var myModule = angular.module('catalog', ['angularUtils.directives.dirPagination'], function($interpolateProvider) {
    $interpolateProvider.startSymbol('<%');
    $interpolateProvider.endSymbol('%>');

  });

    myModule.controller('courseController', function ($scope, $http) {
 // function courseController($scope,$http) {
        $scope.employees = [];
        $scope.sorting = [{'name':'highrate', 'display':'High rating'}, {'name':'lowrate', 'display':'Low rating'},{'name':'latest', 'display':'Latest'},{'name':'oldest', 'display':'Oldest'}];

        $scope.tab= 1;
        $scope.categorylist;
        var url = M.cfg.wwwroot + '/local/catalog/courseajax.php?tab=7';            
        // $http.get(url).success( function(response) {                      
        //   $scope.categorylist =  response;
        // });
        $http.get(url).then(function success(response){
          $scope.categorylist =  response.data;
        }, function error(err) {
          console.log("Error: " + err);
        })
        $scope.tabfunction = function(tab, page, search_criteria, category, enrolltype, sortid, fromtab) {
        if (page<1) {
            page=1;
        }
        if(typeof page == 'undefined'){
           page=1;
        }
        
        if(typeof search_criteria == 'undefined'){
           search_criteria=null;
        }
        
        if(typeof enrolltype == 'undefined'){
           enrolltype=0;
        }
        
        if (fromtab==1) {
          angular.element('#enrolltype').val(0);
          angular.element('#search').val('');
        }   
        $scope.sortid=sortid;
        $scope.tab=tab; 
          if (tab) {
            $.each([ 1,2,3,4,5,6,7,8 ], function( index, value ) {               
              if (tab==value) {                        
                angular.element('.tab'+tab).addClass('active');
              }else{                         
                if(angular.element('.tab'+value).hasClass('active')){                              
                  angular.element('.tab'+value).removeClass('active');
                }
              }
            });
          }

          $scope.showLoader = true;
           var url = M.cfg.wwwroot + '/local/catalog/courseajax.php?tab='+tab+'&page='+page+'&search='+search_criteria+'&category='+category+'&enrolltype='+enrolltype+'&sortid='+sortid;
            
            // $http.get(url).success( function(response) {
            //      //console.log(response);
            //       $scope.showLoader = false;  
            //      $scope.courseinfo = response;                
            //      $scope.numberofrecords =  response.numberofrecords;                 
            // });

            $http.get(url).then(function success(response){
              $scope.showLoader = false;  
              $scope.courseinfo = response.data;                
              $scope.numberofrecords =  response.data.numberofrecords;          
            }, function error(err) {
              console.log("Error: " + err);
            })
          }

          $scope.categories = function(){ 
            $scope.categorylist;
            var url = M.cfg.wwwroot + '/local/catalog/courseajax.php?tab=7';            
            // $http.get(url).success( function(response) {                    
                //  $scope.categorylist =  response;
            // }); 
            $http.get(url).then(function success(response){
              $scope.categorylist =  response.data;
            }, function error(err) {
              console.log("Error: " + err);
            });
          };       
        
        
          $scope.init = function( tab){     
            $scope.showLoader = true; 
            
            $scope.tabfunction(tab,0);            
          };        

          $scope.pageChangeHandler = function(num,tab) {
               var categoryid=angular.element('#categoryid').val();
               
               var search_criteria=angular.element('#search').val();
               var enrolltype=angular.element('#enrolltype').val();
               var sortid=angular.element('#sortid').val();
               // alert(num+'---'+tab);
               // console.log(tab);
               if (tab == 1) {
                  var categoryid=angular.element('#categoryid').val();
                  $scope.tabfunction(tab,num,search_criteria, categoryid,enrolltype, sortid,tab);
               }else{
                  $scope.tabfunction(tab,num, search_criteria,categoryid,enrolltype, sortid,tab);
               }
          };
    
          $scope.filterbyname= function(tab){          
               var search_criteria=angular.element('#search').val();
               //console.log(search_criteria);
               var enrolltype=angular.element('#enrolltype').val();
               if (tab==1) {
                    var categoryid=angular.element('#categoryid').val();
                    $scope.tabfunction(tab,0,search_criteria, categoryid,enrolltype);
               }
               else
               $scope.tabfunction(tab,0,search_criteria,0, enrolltype);
          };
          
          $scope.modelidchange = function (tab) {
               var categoryid=angular.element('#categoryid').val();
               var search_criteria=angular.element('#search').val();
               var enrolltype=angular.element('#enrolltype').val();
               var sortid=angular.element('#sortid').val();
               $scope.tabfunction(tab,0,search_criteria,categoryid,enrolltype, sortid,tab );
          }

          $scope.sortidchange = function (tab) {
               var categoryid=angular.element('#categoryid').val();
               var search_criteria=angular.element('#search').val();
               var enrolltype=angular.element('#enrolltype').val();
               var sortid=angular.element('#sortid').val();
               $scope.tabfunction(tab,0,search_criteria,categoryid,enrolltype, sortid,tab);
          }
          
          
          $scope.enrolltypechange= function (tab){             
               var categoryid=angular.element('#categoryid').val();
               var search_criteria=angular.element('#search').val();          
               var enrolltype=angular.element('#enrolltype').val();    
               var sortid=angular.element('#sortid').val();     
               $scope.tabfunction(tab,0,search_criteria,categoryid, enrolltype, sortid,tab );                          
          } // end of  enrolltypechange function
     
    }); 
    
    myModule.filter('unsafe', ['$sce', function ($sce) {
        return function (val) {
            return $sce.trustAsHtml(val);
        };
    }]);
