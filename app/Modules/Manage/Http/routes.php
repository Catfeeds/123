<?php

//登录
Route::get('/manage/login',  'Auth\AuthController@getLogin')->name('loginCreatePage');
Route::group(['middleware' => 'systemlog'], function() {
Route::post('/manage/login', 'Auth\AuthController@postLogin')->name('loginCreate');
});
//退出登录
Route::get('/manage/logout', 'Auth\AuthController@getLogout')->name('logout');

//Route::group(['prefix' => 'manage', 'middleware' => ['RolePermission','systemlog']], function() {
Route::group(['prefix' => 'manage'], function() {
    Route::get('/', 'IndexController@getManage')->name('backstagePage');

    Route::get('/addRole', 'IndexController@addRole')->name('roleCreate');
    Route::get('/addPermission', 'IndexController@addPermission')->name('permissionCreate');
    Route::get('/attachRole', 'IndexController@attachRole')->name('attachRoleCreate');  
    Route::get('/attachPermission', 'IndexController@attachPermission')->name('attachPermissionCreate');
    //实名认证
    Route::get('/realnameAuthList', 'AuthController@realnameAuthList')->name('realnameAuthList');
    Route::get('/realnameAuthHandle/{id}/{action}', 'AuthController@realnameAuthHandle')->name('realnameAuthHandle');
    Route::get('/realnameAuth/{id}', 'AuthController@realnameAuth')->name('realnameAuth');
    //学历认证
    Route::get('educationAuth','AuthController@studentAuthList')->name('studentAuthList');
    Route::get('/educationAuthHandle/{id}/{action}','AuthController@educationAuthHandle');//认证
    Route::get('/educationAuthInfo/{id}','AuthController@educationAuthInfo')->name('educationAuthInfo');

    //支付宝认证
    Route::get('/alipayAuthList', 'AuthController@alipayAuthList')->name('alipayAuthList');//列表
    Route::get('/alipayAuthHandle/{id}/{action}', 'AuthController@alipayAuthHandle')->name('alipayAuthHandle');//审批
    Route::post('/alipayAuthMultiHandle', 'AuthController@alipayAuthMultiHandle')->name('alipayAuthMultiHandle');
    Route::get('alipayAuth/{id}', 'AuthController@getAlipayAuth')->name('alipayAuth');
    Route::post('alipayAuthPay', 'AuthController@alipayAuthPay')->name('alipayAuthPayCreate');

    
    Route::get('/bankAuthList', 'AuthController@bankAuthList')->name('bankAuthList');
    Route::get('/bankAuthHandle/{id}/{action}', 'AuthController@bankAuthHandle')->name('bankAuthHandle');
    Route::post('/bankAuthMultiHandle', 'AuthController@bankAuthMultiHandle')->name('bankAuthMultiHandle');
    Route::get('/bankAuth/{id}', 'AuthController@getBankAuth')->name('bankAuth');
    Route::post('bankAuthPay', 'AuthController@bankAuthPay')->name('bankAuthPayCreate');


    
    Route::get('/taskList', 'TaskController@taskList')->name('taskList');
    Route::get('/taskPass/{id}', 'TaskController@taskPass')->name('taskPass');//任务审核通过
    Route::get('/taskNotPass/{id}', 'TaskController@taskNotPass')->name('taskNotPass');//任务审核通过
    Route::post('/taskHandle', 'TaskController@taskHandle')->name('taskUpdate');
    Route::post('/taskMultiHandle', 'TaskController@taskMultiHandle')->name('taskMultiUpdate');
    Route::get('/taskDetail/{id}', 'TaskController@taskDetail')->name('taskDetail');
    Route::post('/taskDetailUpdate', 'TaskController@taskDetailUpdate')->name('taskDetailUpdate');
    Route::get('/taskMassageDelete/{id}', 'TaskController@taskMassageDelete')->name('taskMassageDelete');
    
	Route::get('/bidList', 'BidController@bidList')->name('bidList');
	Route::get('/bidDetail/{id}', 'BidController@bidDetail')->name('bidDetail');
	Route::get('/bidConfig/{id}', 'BidController@bidConfig')->name('bidConfig');
	Route::post('/bidConfigUpdate', 'BidController@bidConfigUpdate')->name('bidConfigUpdate');
    
    Route::get('/financeList', 'FinanceController@financeList')->name('financeList');
    Route::get('/financeListExport/{param}', 'FinanceController@financeListExport')->name('financeListExportCreate');
    Route::get('/userFinanceListExport/{param}', 'FinanceController@userFinanceListExport')->name('userFinanceListExportCreate');
    Route::get('/financeStatement', 'FinanceController@financeStatement')->name('financeStatementList');
    Route::get('/financeRecharge', 'FinanceController@financeRecharge')->name('financeRechargeList');
    Route::get('/financeRechargeExport/{param}', 'FinanceController@financeRechargeExport')->name('financeRechargeExportCreate');
    Route::get('/financeWithdraw', 'FinanceController@financeWithdraw')->name('financeWithdrawList'); //提现记录
    Route::get('/financeWithdrawExport/{param}', 'FinanceController@financeWithdrawExport')->name('financeWithdrawExportCreate');
    Route::get('/financeProfit', 'FinanceController@financeProfit')->name('financeProfitList');

    
    Route::get('/area','AreaController@areaList')->name('areaList');
    Route::post('/areaCreate','AreaController@areaCreate')->name('areaCreate');
    Route::get('/areaDelete/{id}','AreaController@areaDelete')->name('areaDelete');
    Route::get('/ajaxcity','AreaController@ajaxCity')->name('ajaxCity');
    Route::get('/ajaxarea','AreaController@ajaxArea')->name('ajaxArea');
    Route::get('/ajaxRegionProvince','AreaController@ajaxRegionProvince')->name('ajaxRegionProvince');
    Route::get('/ajaxProvinceSchool','AreaController@ajaxProvinceSchool')->name('ajaxProvinceSchool');

    
    Route::get('/industry','IndustryController@industryList')->name('industryList');
    Route::post('/industryCreate','IndustryController@industryCreate')->name('industryCreate');
    Route::get('/industryDelete/{id}','IndustryController@industryDelete')->name('industryDelete');
    Route::get('/ajaxSecond','IndustryController@ajaxSecond')->name('ajaxSecond');
    Route::get('/ajaxThird','IndustryController@ajaxThird')->name('ajaxThird');
    Route::get('/tasktemplate/{id}','IndustryController@taskTemplates')->name('taskTemplates');
    Route::post('/templateCreate','IndustryController@templateCreate')->name('templateCreate');
    Route::get('/industryInfo/{id}','IndustryController@industryInfo')->name('industryDetail');
    Route::post('/industryInfo','IndustryController@postIndustryInfo')->name('postIndustryDetail');


    Route::get('/userFinance', 'FinanceController@userFinance')->name('userFinanceCreate');
    Route::get('/cashoutList', 'FinanceController@cashoutList')->name('cashoutList');//申请提现列表
    Route::get('/cashoutHandle/{id}/{action}', 'FinanceController@cashoutHandle')->name('cashoutUpdate');
    Route::get('cashoutInfo/{id}', 'FinanceController@cashoutInfo')->name('cashoutDetail');

    Route::get('userRecharge', 'FinanceController@getUserRecharge')->name('userRechargePage');
    Route::post('userRecharge', 'FinanceController@postUserRecharge')->name('userRechargeUpdate');
    Route::get('rechargeList', 'FinanceController@rechargeList')->name('rechargeList'); //充值记录
    Route::get('confirmRechargeOrder/{order}', 'FinanceController@confirmRechargeOrder')->name('confirmRechargeOrder');

    
    Route::get('/config', 'ConfigController@getConfigBasic')->name('configDetail');
    Route::get('/config/basic', 'ConfigController@getConfigBasic')->name('basicConfigDetail');
    Route::post('/config/basic', 'ConfigController@saveConfigBasic')->name('configBasicUpdate');
    Route::get('/config/seo', 'ConfigController@getConfigSEO')->name('seoConfigDetail');
    Route::post('/config/seo', 'ConfigController@saveConfigSEO')->name('configSeoUpdate');
    Route::get('/config/nav', 'ConfigController@getConfigNav')->name('navConfigDetail');
    Route::post('/config/nav', 'ConfigController@postConfigNav')->name('configNavCreate');
    Route::get('/config/nav/{id}/delete', 'ConfigController@deleteConfigNav')->name('configNavDelete');
    Route::get('/config/attachment', 'ConfigController@getAttachmentConfig')->name('attachmentConfigDetail');
    Route::post('/config/attachment', 'ConfigController@postAttachmentConfig')->name('attachmentConfigCreate');

    Route::get('/config/site', 'ConfigController@getConfigSite')->name('siteConfigDetail');
    Route::post('/config/site', 'ConfigController@saveConfigSite')->name('configSiteUpdate');
    //后台邮箱设置
    Route::get('/config/email', 'ConfigController@getConfigEmail')->name('emailConfigDetail');
    Route::post('/config/email', 'ConfigController@saveConfigEmail')->name('configEmailUpdate');

    Route::post('/config/sendEmail', 'ConfigController@sendEmail')->name('sendEmail');

    Route::get('/config/link', 'ConfigController@configLink')->name('configLink');
    Route::post('/config/link', 'ConfigController@link')->name('postConfigLink');

    
    Route::get('/taskConfig/{id}','TaskConfigController@index')->name('taskConfigPage');
    Route::post('/taskConfigUpdate','TaskConfigController@update')->name('taskConfigUpdate');
    Route::get('/ajaxUpdateSys','TaskConfigController@ajaxUpdateSys')->name('ajaxUpdateSys');
    Route::post('/baseConfig','TaskConfigController@baseConfig')->name('baseConfigCreate');


    
    Route::get('payConfig', 'InterfaceController@getPayConfig')->name('payConfigDetail');
    Route::post('payConfig', 'InterfaceController@postPayConfig')->name('payConfigUpdate');
    Route::get('thirdPay', 'InterfaceController@getThirdPay')->name('thirdPayDetail');
    Route::get('thirdPayHandle/{id}/{action}', 'InterfaceController@thirdPayHandle')->name('thirdPayStatusUpdate');
    Route::get('thirdPayEdit/{id}', 'InterfaceController@getThirdPayEdit')->name('thirdPayUpdatePage');
    Route::post('thirdPayEdit', 'InterfaceController@postThirdPayEdit')->name('thirdPayUpdate');

    
    Route::get('thirdLogin', 'InterfaceController@getThirdLogin')->name('thirdLoginPage');
    Route::post('thirdLogin', 'InterfaceController@postThirdLogin')->name('thirdLoginCreate');

    //文章
    Route::get('/article/{upID}','ArticleController@articleList')->name('articleList'); 
    Route::get('/articleFooter/{upID}','ArticleController@articleList')->name('articleFooterList'); 
    Route::get('/addArticle/{upID}','ArticleController@addArticle')->name('articleCreatePage'); 
    Route::get('/addArticleFooter/{upID}','ArticleController@addArticle')->name('articleFooterCreatePage'); 
    Route::post('/addArticle', 'ArticleController@postArticle')->name('articleCreate'); 
    Route::get('/articleDelete/{id}/{upID}','ArticleController@articleDelete')->name('articleDelete'); 
    Route::get('/editArticle/{id}/{upID}','ArticleController@editArticle')->name('articleUpdatePage'); 
    Route::get('/editArticleFooter/{id}/{upID}','ArticleController@editArticle')->name('articleFooterUpdatePage'); 
    Route::post('/editArticle', 'ArticleController@postEditArticle')->name('articleUpdate'); 
    Route::post('/allDelete', 'ArticleController@allDelete')->name('allDelete'); 

    //校园招聘
    Route::get('/campus','ArticleController@recruitmentList')->name('recruitmentList');//列表
    Route::get('/addCampus','ArticleController@addCampusPage')->name('addCampusPage');//添加展示页
    Route::post('/campus','ArticleController@addRecruitment');//添加
    Route::get('/campusDetail/{id}','ArticleController@recruitmentDet')->name('recruitmentDet');//详情
    Route::post('/campusUpdate','ArticleController@recruitmentUp');//修改
    Route::get('/campusDel/{id}','ArticleController@recruitmentDel');//删除
    Route::post('/campusHandle','ArticleController@recruitmentDelHandle');//批量删除

    //系统任务
    Route::get('system','ArticleController@systemTask')->name('systemTask');
    Route::get('systemAdd','ArticleController@addSystemShow')->name('addSystemShow');//添加页
    Route::post('addSystem','ArticleController@systemCreate');//添加
    Route::get('systemDel/{id}','ArticleController@systemDelete');//删除
    Route::post('systemHandle','ArticleController@systemTaskDelHandle');//批量删除
    Route::get('systemDet/{id}','ArticleController@systemDetail')->name('systemDetail');//详情
    Route::post('systemUp','ArticleController@systemUpdate');//修改

    //分类
    Route::get('/categoryList/{upID}','ArticleCategoryController@categoryList')->name('categoryList'); 
    Route::get('/categoryFooterList/{upID}','ArticleCategoryController@categoryList')->name('categoryFooterList'); 
    Route::get('/categoryDelete/{id}/{upID}','ArticleCategoryController@categoryDelete')->name('categoryDelete'); 
    Route::get('/categoryAdd/{upID}','ArticleCategoryController@categoryAdd')->name('categoryCreatePage'); 
    Route::post('/categoryAdd', 'ArticleCategoryController@postCategory')->name('categoryCreate');
    Route::get('/categoryEdit/{id}/{upID}','ArticleCategoryController@categoryEdit')->name('categoryUpdatePage');
    Route::post('/categoryEdit','ArticleCategoryController@postEditCategory')->name('categoryUpdate');
    Route::post('/categoryAllDelete','ArticleCategoryController@cateAllDelete')->name('categoryAllDelete');
    Route::get('/getChildCateList/{id}','ArticleCategoryController@getChildCateList')->name('getChildCateList'); 
    Route::get('/categoryFooterAdd/{upID}','ArticleCategoryController@categoryAdd')->name('categoryFooterCreatePage'); 
    Route::get('/categoryFooterEdit/{id}/{upID}','ArticleCategoryController@categoryEdit')->name('categoryFooterUpdatePage');
    Route::get('/add/{upID}','ArticleCategoryController@add')->name('addCategory');
    Route::get('/edit/{id}/{upID}','ArticleCategoryController@edit')->name('editCategory');


    
    Route::get('/successCaseList','SuccessCaseController@successCaseList')->name('successCaseList');
    Route::get('/successCaseAdd','SuccessCaseController@create')->name('successCaseCreatePage');
    Route::post('/successCaseUpdate','SuccessCaseController@update')->name('successCaseCreate');
    Route::get('/successCaseDel/{id}','SuccessCaseController@successCaseDel')->name('successCaseDel');
    Route::post('/ajaxGetSecondCate','SuccessCaseController@ajaxGetSecondCate')->name('ajaxGetSecondCate');

    
    Route::get('/navList','NavController@navList')->name('navList'); 
    Route::get('/addNav','NavController@addNav')->name('navCreatePage');  
    Route::post('/addNav','NavController@postAddNav')->name('navCreate'); 
    Route::get('/editNav/{id}','NavController@editNav')->name('navUpdatePage'); 
    Route::post('/editNav','NavController@postEditNav')->name('navUpdate'); 
    Route::get('/deleteNav/{id}','NavController@deleteNav')->name('navDelete');
    Route::get('/isFirst/{id}','NavController@isFirst')->name('isFirst'); 

    
    Route::get('/userList', 'UserController@getUserList')->name('userList');
    Route::get('/handleUser/{uid}/{action}', 'UserController@handleUser')->name('userStatusUpdate');
    Route::get('/userAdd', 'UserController@getUserAdd')->name('userCreatePage');
    Route::post('/userAdd', 'UserController@postUserAdd')->name('userCreate');
    Route::post('checkUserName', 'UserController@checkUserName')->name('checkUserName');
    Route::post('checkEmail', 'UserController@checkEmail')->name('checkEmail');
    Route::get('/userEdit/{uid}', 'UserController@getUserEdit')->name('userUpdatePage');
    Route::post('/userEdit', 'UserController@postUserEdit')->name('userUpdate');
    Route::get('/managerList', 'UserController@getManagerList')->name('managerList');  //系统用户
    Route::get('/handleManage/{uid}/{action}', 'UserController@handleManage')->name('userStatusUpdate');
    Route::get('/managerAdd', 'UserController@managerAdd')->name('managerCreatePage');
    Route::post('/managerAdd', 'UserController@postManagerAdd')->name('managerCreate');
    Route::post('checkManageName', 'UserController@checkManageName')->name('checkManageName');
    Route::post('checkManageEmail', 'UserController@checkManageEmail')->name('checkManageEmail');
    Route::get('/managerDetail/{id}', 'UserController@managerDetail')->name('managerDetail');
    Route::post('/managerDetail', 'UserController@postManagerDetail')->name('managerDetailUpdate');
    Route::get('/managerDel/{id}', 'UserController@managerDel')->name('managerDelete');
    Route::post('/managerDeleteAll', 'UserController@postManagerDeleteAll')->name('managerAllDelete');

    Route::get('/rolesList', 'UserController@getRolesList')->name('rolesList');
    Route::get('/rolesAdd', 'UserController@getRolesAdd')->name('rolesCreatePage');
    Route::post('/rolesAdd', 'UserController@postRolesAdd')->name('rolesCreate');
    Route::get('/rolesDel/{id}', 'UserController@getRolesDel')->name('rolesDelete');
    Route::get('/rolesDetail/{id}', 'UserController@getRolesDetail')->name('rolesDetail');
    Route::post('/rolesDetail', 'UserController@postRolesDetail')->name('rolesDetailUpdate');

    Route::get('/permissionsList', 'UserController@getPermissionsList')->name('permissionsList');//权限设置
    Route::get('/permissionsAdd', 'UserController@getPermissionsAdd')->name('permissionsCreatePage');
    Route::post('/permissionsAdd', 'UserController@postPermissionsAdd')->name('permissionsCreate');
    Route::get('/permissionsDel/{id}', 'UserController@getPermissionsDel')->name('permissionsDelete');
    Route::get('/permissionsDetail/{id}', 'UserController@getPermissionsDetail')->name('permissionsDetail');
    Route::post('/permissionsDetail', 'UserController@postPermissionsDetail')->name('postPermissionsDetailUpdate');

    
    Route::get('/menuList/{id}/{level}','MenuController@getMenuList')->name('getMenuList');
    Route::get('/addMenu/{id?}','MenuController@addMenu')->name('addMenu');
    Route::post('/menuCreate','MenuController@menuCreate')->name('menuCreate');
    Route::get('/menuDelete/{id}','MenuController@menuDelete')->name('menuDelete');
    Route::get('/menuUpdate/{id}','MenuController@menuUpdate')->name('menuUpdate');
    Route::post('/updateMenu','MenuController@updateMenu')->name('updateMenu');
    

    Route::get('/reportList','TaskReportController@reportList')->name('reportList');
    Route::get('/reportDelet/{id}','TaskReportController@reportDelet')->name('reportDelete');
    Route::post('/reportDeletGroup','TaskReportController@reportDeletGroup')->name('reportGroupDelete');
    Route::get('/reportDetail/{id}','TaskReportController@reportDetail')->name('reportDetail');
    Route::post('/handleReport','TaskReportController@handleReport')->name('reportUpdate');

    
    Route::get('/rightsList','TaskRightsController@rightsList')->name('rightsList');
    Route::get('/rightsDelet/{id}','TaskRightsController@rightsDelet')->name('rightsDelete');
    Route::post('/rightsDeletGroup','TaskRightsController@rightsDeletGroup')->name('rightsGroupDelete');
    Route::get('/rightsDetail/{id}','TaskRightsController@rightsDetail')->name('rightsDetail');
    Route::post('/handleRights','TaskRightsController@handleRights')->name('handleRightsCreate');

    
    Route::get('/serviceList','ServiceController@serviceList')->name('adServiceList'); 
    Route::get('/addService','ServiceController@addService')->name('addServiceCreatePage'); 
    Route::post('/addService','ServiceController@postAddService')->name('addServiceCreate');
    Route::get('/editService/{id}','ServiceController@editService')->name('addServiceUpdatePage');
    Route::post('/postEditService','ServiceController@postEditService')->name('addServiceUpdate');
    Route::get('/deleteService/{id}','ServiceController@deleteService')->name('addServiceDelete');
    Route::get('/serviceBuy','ServiceController@serviceBuy')->name('serviceBuyList'); 

    
    Route::get('/link', 'LinkController@linkList')->name('linkList');
    Route::post('/addlink', 'LinkController@postAdd')->name('linkCreate');
    Route::get('/editlink/{id}', 'LinkController@getEdit')->name('linkUpdatePage');
    Route::get('/deletelink/{id}', 'LinkController@getDeleteLink')->name('linkDelete');
    Route::post('/allDeleteLink', 'LinkController@allDeleteLink')->name('allLinkDelete');
    Route::get('/handleLink/{id}/{action}', 'LinkController@handleLink')->name('linkStatusUpdate');
    Route::post('/updatelink/{id}', 'LinkController@postUpdateLink')->name('linkUpdate');


    //用户反馈
    Route::get('/feedbackList', 'FeedbackController@listInfo')->name('feedbackList');
    Route::get('/feedbackDetail/{id}', 'FeedbackController@feedbackDetail')->name('feedbackDetail');//反馈详情
    Route::get('/feedbackReplay/{id}', 'FeedbackController@feedbackReplay')->name('feedbackReplayUpdate');//反馈回复页
    Route::get('/deleteFeedback/{id}', 'FeedbackController@deletefeedback')->name('feedbackDelete');
    Route::get('/feedbackUpdate', 'FeedbackController@feedbackUpdate')->name('feedbackUpdate');

    //推广员管理
    Route::get('/generalize_config','FeedbackController@generalizeConfig')->name('generalizeConfig');//推广配置页
    Route::get('/addGeneralize_config','FeedbackController@addGeneralizeConfig')->name('addGeneralizeConfig');//添加推广配置
    Route::get('/upGeneralize_config/{id}','FeedbackController@upGeneralizeConfig')->name('upGeneralizeConfig');//修改推广配置
    Route::post('/generalize_config','FeedbackController@postGeneralizeConfig');
    Route::post('/generalize_configUp','FeedbackController@postGeneralizeConfigUpdate');//修改
    Route::get('/generalize_list','FeedbackController@generalizeList')->name('generalizeList');//推广员列表页
    Route::post('generalize','FeedbackController@createGeneralize');//添加推广员


    //帮助
    Route::get('help','FeedbackController@helpInfo')->name('helpInfo');
    Route::get('addHelp','FeedbackController@helpAdd')->name('helpAdd');
    Route::post('help','FeedbackController@createHelp');
    Route::get('helpDel/{id}','FeedbackController@helpDelete');//删除
    Route::get('helpDet/{id}','FeedbackController@helpDetail')->name('helpDetail');//详情
    Route::post('helpDelHandle','FeedbackController@helpDelHandle');//批量删除
    Route::post('helpUp','FeedbackController@helpUpdate');

    //物品配送finish
    Route::get('/distribution','GoodsDistributionController@distributionList')->name('distributionList');
    Route::post('/finishDistribution','GoodsDistributionController@finishDistribution');
    Route::get('/inputLogistics/{id}','GoodsDistributionController@inputLogistics')->name('inputLogistics');

    Route::get('/hotwordsList','HotwordsController@hotwordsInfo')->name('hotwordsList');
    Route::post('/hotwordsCreate','HotwordsController@hotwordsCreate')->name('hotwordsCreate');
    Route::get('/listorderUpdate','HotwordsController@listorderUpdate')->name('listorderUpdate');
    Route::get('/hotwordsDelete/{id}','HotwordsController@hotwordsDelete')->name('hotwordsDelete');
    Route::get('/hotwordsMulDelte','HotwordsController@hotwordsMulDelte')->name('hotwordsMulDelete');

    
    Route::get('attachmentList', 'ToolController@getAttachmentList')->name('attachmentList');
    Route::get('attachmentDel/{id}', 'ToolController@attachmentDel')->name('attachmentDelete');


    
    Route::get('/messageList','MessageController@messageList')->name('messageList');
    Route::get('/editMessage/{id}','MessageController@editMessage')->name('messageUpdatePage'); 
    Route::post('/editMessage','MessageController@postEditMessage')->name('messageUpdate'); 
    Route::get('/changeStatus/{id}/{isName}/{status}','MessageController@changeStatus')->name('messageStatusUpdate'); 

    
    Route::get('/systemLogList','SystemLogController@systemLogList')->name('systemLogList');
    Route::get('/systemLogDelete/{id}','SystemLogController@systemLogDelete')->name('systemLogDelete');
    Route::get('/systemLogDeleteAll','SystemLogController@systemLogDeleteAll')->name('systemLogDeleteAll');
    Route::post('/systemLogMulDelete','SystemLogController@systemLogMulDelete')->name('systemLogMulDelete');

    
    Route::get('/getCommentList','TaskCommentController@getCommentList')->name('commentList');
    Route::get('/commentDel/{id}','TaskCommentController@commentDel')->name('commentDelete');
	
    
    Route::get('/agreementList','AgreementController@agreementList')->name('agreementList'); 
    Route::get('/addAgreement','AgreementController@addAgreement')->name('agreementCreatePage');
    Route::post('/addAgreement','AgreementController@postAddAgreement')->name('agreementCreate');
    Route::get('/editAgreement/{id}','AgreementController@editAgreement')->name('agreementUpdatePage');
    Route::post('/editAgreement','AgreementController@postEditAgreement')->name('agreementUpdate');
    Route::get('/deleteAgreement/{id}','AgreementController@deleteAgreement')->name('agreementDelete');

    
    Route::get('/skin','AgreementController@skin')->name('manageSkin');
    Route::get('/skinChange/{name}','AgreementController@skinChange')->name('skinChange');
    Route::get('/skinSet/{number}','AgreementController@skinSet')->name('skinSet');
    
    Route::get('/aboutUs','ConfigController@aboutUs')->name('aboutUs');

    
    Route::get('/employConfig','EmployController@employConfig')->name('employConfig');
    Route::get('/employList','EmployController@employList')->name('employList');
    Route::get('/employEdit/{id}','EmployController@employEdit')->name('employEdit');
    Route::post('/employUpdate','EmployController@employUpdate')->name('employUpdate');
    Route::get('/employDelete/{id}','EmployController@employDelete')->name('employDelete');
    Route::get('/download/{id}','EmployController@download')->name('download');
    Route::post('/configUpdate','EmployController@configUpdate')->name('configUpdate');

    
    Route::get('/enterpriseAuthList', 'AuthController@enterpriseAuthList')->name('enterpriseAuthList');
    Route::get('/enterpriseAuthHandle/{id}/{action}', 'AuthController@enterpriseAuthHandle')->name('enterpriseAuthHandle');
    Route::get('/enterpriseAuth/{id}', 'AuthController@enterpriseAuth')->name('enterpriseAuth');
    Route::post('/allEnterprisePass', 'AuthController@allEnterprisePass')->name('allEnterprisePass');
    Route::post('/allEnterpriseDeny', 'AuthController@allEnterpriseDeny')->name('allEnterpriseDeny');

    
    Route::get('/shopList', 'ShopController@shopList')->name('shopList');
    Route::get('/shopInfo/{id}', 'ShopController@shopInfo')->name('shopInfo');
    Route::post('/updateShopInfo', 'ShopController@updateShopInfo')->name('updateShopInfo');
    Route::get('/openShop/{id}', 'ShopController@openShop')->name('openShop');
    Route::get('/closeShop/{id}', 'ShopController@closeShop')->name('closeShop');
    Route::get('/recommendShop/{id}', 'ShopController@recommendShop')->name('recommendShop');
    Route::get('/removeRecommendShop/{id}', 'ShopController@removeRecommendShop')->name('removeRecommendShop');
    Route::post('/allOpenShop', 'ShopController@allOpenShop')->name('allOpenShop');
    Route::post('/allCloseShop', 'ShopController@allCloseShop')->name('allCloseShop');

    Route::get('/shopConfig', 'ShopController@shopConfig')->name('shopConfig');
    Route::post('/postShopConfig', 'ShopController@postShopConfig')->name('postShopConfig');

    Route::get('/goodsList', 'GoodsController@goodsList')->name('goodsList');
    Route::get('/goodsInfo/{id}', 'GoodsController@goodsInfo')->name('goodsInfo');
    Route::get('/goodsComment/{id}', 'GoodsController@goodsComment')->name('goodsComment');
    Route::post('/saveGoodsInfo', 'GoodsController@saveGoodsInfo')->name('saveGoodsInfo');
    Route::post('/changeGoodsStatus', 'GoodsController@changeGoodsStatus')->name('changeGoodsStatus');
    Route::post('/checkGoodsDeny', 'GoodsController@checkGoodsDeny')->name('checkGoodsDeny');
    Route::post('/ajaxGetSecondCate', 'GoodsController@ajaxGetSecondCate')->name('ajaxGetSecondCate');

    Route::get('/goodsConfig', 'GoodsController@goodsConfig')->name('goodsConfig');
    Route::post('/postGoodsConfig', 'GoodsController@postGoodsConfig')->name('postGoodsConfig');

    Route::get('/ShopRightsList', 'ShopController@rightsList')->name('ShopRightsList');
    Route::get('/shopRightsInfo/{id}', 'ShopController@shopRightsInfo')->name('shopRightsInfo');
    Route::post('/download', 'ShopController@download')->name('download');
    Route::get('/ShopRightsSuccess/{id}', 'ShopController@ShopRightsSuccess')->name('ShopRightsSuccess');
    Route::post('/serviceRightsSuccess', 'ShopController@serviceRightsSuccess')->name('serviceRightsSuccess');
    Route::get('/ShopRightsFailure/{id}', 'ShopController@ShopRightsFailure')->name('ShopRightsFailure');
    Route::get('/serviceRightsFailure/{id}', 'ShopController@serviceRightsFailure')->name('serviceRightsFailure');
    Route::get('/deleteShopRights/{id}', 'ShopController@deleteShopRights')->name('deleteShopRights');

    Route::get('/shopOrderList', 'ShopOrderController@orderList')->name('shopOrderList');
    Route::get('/shopOrderInfo/{id}', 'ShopOrderController@shopOrderInfo')->name('shopOrderInfo');


    Route::get('/goodsServiceList','GoodsServiceController@goodsServiceList')->name('goodsServiceList');
    Route::get('/serviceOrderList','GoodsServiceController@serviceOrderList')->name('serviceOrderList');
    Route::get('/serviceOrderInfo/{id}','GoodsServiceController@serviceOrderInfo')->name('serviceOrderInfo');
    Route::get('/serviceConfig','GoodsServiceController@serviceConfig')->name('serviceConfig');
    Route::get('/serviceInfo/{id}','GoodsServiceController@serviceInfo')->name('serviceInfo');
    Route::post('/serviceConfigUpdate','GoodsServiceController@serviceConfigUpdate')->name('serviceConfigUpdate');
    Route::get('/serviceComments/{id}','GoodsServiceController@serviceComments')->name('serviceComments');
    Route::post('/saveServiceInfo','GoodsServiceController@saveServiceInfo')->name('saveServiceInfo');
    Route::get('/checkServiceDeny','GoodsServiceController@checkServiceDeny')->name('checkServiceDeny');
    Route::get('/changeServiceStatus','GoodsServiceController@changeServiceStatus')->name('changeServiceStatus');
    Route::get('/serviceOrderEdit/{id}','GoodsServiceController@serviceOrderEdit')->name('serviceOrderEdit');
    Route::post('/serviceOrderUpdate','GoodsServiceController@serviceOrderUpdate')->name('serviceOrderUpdate');


    Route::get('/questionList','QuestionController@getList')->name('questionList');
    Route::get('/verify/{id}/{status}','QuestionController@verify')->name('verify');
    Route::get('/getDetail/{id}','QuestionController@getDetail')->name('getDetail');
    Route::post('/postDetail','QuestionController@postDetail')->name('postDetail');
    Route::get('/getDetailAnswer/{id}','QuestionController@getDetailAnswer')->name('getDetailAnswer');
    Route::get('/questionConfig','QuestionController@getConfig')->name('questionConfig');
    Route::post('/postConfig','QuestionController@postConfig')->name('postConfig');
    Route::get('/ajaxCategory/{id}','QuestionController@ajaxCategory')->name('ajaxCategory');
    Route::get('/questionDelete/{id}','QuestionController@questionDelete')->name('questionDelete');


    Route::get('/download/{id}','TaskController@download');
    //推广配置
    Route::get('/promoteConfig','PromoteController@promoteConfig')->name('promoteConfig');
    Route::post('/promoteConfig','PromoteController@postPromoteConfig')->name('postPromoteConfig');
    Route::post('/addExtendProfit','PromoteController@addExtendProfit');//添加推广人数收益
    Route::get('/deleteExtendProfit/{id}','PromoteController@deleteExtendProfit');//删除推广人数收益
    Route::get('/extendProfitDet/{id}','PromoteController@extendProfitDet')->name('extendProfitDet');
    Route::post('/extendProfitUp','PromoteController@ExtendProfitUpdate');//修改推广人数收益
    Route::post('/addInvitingAwards','PromoteController@addInvitingAwards');//添加推广名次奖励
    Route::post('/invitingAwards','PromoteController@invitingAwards');//添加推广名次奖励
    Route::get('/deleteInvitingAwards/{id}','PromoteController@deleteInvitingAwards');//删除推广名次奖励
    Route::get('/promoteRelation','PromoteController@promoteRelation')->name('promoteRelation');
    Route::get('/promoteFinance','PromoteController@promoteFinance')->name('promoteFinance');  //推广财务

    //邀请码管理
    Route::get('/invitationList', 'PromoteController@invitationList')->name('invitationList');   //邀请码列表
    Route::get('/invitation', 'PromoteController@invitation')->name('invitation');   //邀请码生成
    Route::post('/createInvitation', 'PromoteController@createInvitation')->name('createInvitation');   //生成邀请码
    
    //用户等级   
    Route::get('/grade', 'PromoteController@gradeList')->name('gradeList');
    Route::post('/postuerGrade', 'PromoteController@postuerGrade')->name('postuerGrade');
    Route::get('/gradeDel/{id}', 'PromoteController@gradeDel');  //删除用户等级
    Route::get('/gradeDetail/{id}', 'PromoteController@gradeDetail')->name('gradeUpdateDetail');  //用户等级详情
    Route::post('/gradeUpdate', 'PromoteController@gradeUpdate');  //修改用户等级
    //签到
    Route::get('/sign', 'PromoteController@getSign')->name('getSign');
    Route::post('/sign','PromoteController@postAddSign')->name('postAddSign');//添加签到
    Route::get('/signDetail/{id}', 'PromoteController@signDetail')->name('signUpdateDetail');  //签到详情
    Route::post('/signUpdate', 'PromoteController@signUpdate');  //修改签到     
    Route::get('/signPrize/{id}', 'PromoteController@setupSignPrize')->name('setupSignPrize');   //签到奖品
    Route::post('/signPrize', 'PromoteController@addSignPrize');   //添加签到奖品
    Route::get('/signDel/{id}','PromoteController@signPrizeDel')->name('signPrizeDel');    //删除签到奖品
    Route::get('/prizeDetail/{id}','PromoteController@signPrizeDetail')->name('signPrizeDetail');    //签到奖品详情
    Route::post('/updateSign', 'PromoteController@updateSignPrize');   //修改签到奖品
    //奖品管理
    Route::get('/prizeManage','EmployerHunController@prizeManageList')->name('prizeManageList');
    Route::post('/addPrize','EmployerHunController@createPrize');//添加奖品
    Route::get('/prizeDel/{id}','EmployerHunController@prizeDelete');//删除奖品
    Route::get('/prizeDet/{id}','EmployerHunController@prizeDetail')->name('prizeDetail');    //签到奖品详情
    Route::post('/updatePrize','EmployerHunController@prizeUpdate');//修改奖品

    //雇主等级
    Route::get('/employer','EmployerHunController@employerList')->name('employerList');
    Route::post('/employer','EmployerHunController@postAddEmployer');//添加雇主等级
    Route::get('/employerDel/{id}', 'EmployerHunController@employerDel');  //删除雇主等级 employerDetail
    Route::get('/employerDetail/{id}', 'EmployerHunController@employerDetail')->name('employerGradeDetail');  //用户等级详情
    Route::post('/employerUpdate', 'EmployerHunController@employerUpdate');  //修改雇主等级

    //猎人等级
    Route::get('/hunter','EmployerHunController@hunterList')->name('hunterList');
    Route::post('/hunter','EmployerHunController@postAddHunter');//添加雇主等级
    Route::get('/hunterDel/{id}', 'EmployerHunController@hunterDel');  //删除雇主等级 employerDetail
    Route::get('/hunterDetail/{id}', 'EmployerHunController@hunterDetail')->name('hunterGradeDetail');  //用户等级详情
    Route::post('/hunterUpdate', 'EmployerHunController@hunterUpdate');  //修改雇主等级

    //轮播图或菜单管理  Icon management
    Route::get('/icon/{type?}','IconManagementController@iconList')->name('iconList');
    Route::get('/addIcon','IconManagementController@addIcon')->name('addIcon');
    Route::post('/icon','IconManagementController@postAddIcon');//添加
    Route::get('/iconDel/{id}', 'IconManagementController@iconDel');  //删除
    Route::get('/iconDetail/{id}', 'IconManagementController@iconDetail')->name('iconGradeDetail');  //详情
    Route::post('/iconUpdate', 'IconManagementController@iconUpdate');  //修改

    //游戏比赛信息
    Route::get('/game/single', 'GameController@single')->name('single');  //个人
    Route::match(['get', 'post'], 'game/{id}/edit', 'GameController@singleEdit')->name('singleEdit');
    Route::get('/game/team/{type}', 'GameController@team')->name('team');  //团队
    Route::post('/game/teamDel', 'GameController@teamDel');
    Route::get('game/{id}/teamDetail', 'GameController@teamDetail')->name('teamDetail');
    Route::match(['get', 'post'], 'game/add', 'GameController@teamAdd')->name('teamAdd');
    Route::match(['get', 'post'], 'game/{id}/teamEdit', 'GameController@teamEdit')->name('teamEdit');

    //赛制
    Route::get('/game/competition', 'GameController@competition')->name('competition');
    Route::match(['get', 'post'], 'game/competition/add/{id}', 'GameController@competitionAdd')->name('competitionAdd');
    Route::post('/game/competitionDel', 'GameController@competitionDel');
    Route::get('/game/gameConfig', 'GameController@gameConfig')->name('gameConfig');
    Route::post('/game/groupInitialize','GameController@gameGroupInitialize');

    //自定义任务
    Route::get('/taskWaterList', 'TaskWaterController@taskWaterList')->name('taskWaterList');
    Route::get('/taskWater/{id}/taskDetail', 'TaskWaterController@taskwaterDetail')->name('taskwaterDetail');
    Route::match(['get', 'post'], 'taskWater/add', 'TaskWaterController@taskwaterAdd')->name('taskwaterAdd');
    Route::get('/imageStore', 'TaskWaterController@imageStore')->name('imageStore');
    Route::post('/imageStoreDel', 'TaskWaterController@imageStoreDel');
    Route::match(['get', 'post'], 'imageStore/add', 'TaskWaterController@imageStoreAdd')->name('imageStoreAdd');
    Route::post('/imageUpload', 'TaskWaterController@imageUpload');

    //功能管理
    Route::get('/function','IconManagementController@functionList')->name('functionList');
    Route::post('/function','IconManagementController@postAddFunction');//添加雇主等级
    Route::get('/functionDel/{id}', 'IconManagementController@functionDel');  //删除雇主等级 employerDetail
    Route::get('/functionDetail/{id}', 'IconManagementController@functionDetail')->name('functionGradeDetail');  //用户等级详情
    Route::post('/functionUpdate', 'IconManagementController@functionUpdate');  //修改雇主等级

    //宝箱管理 treasure_chest
    Route::get('/treasure','IconManagementController@treasureChestList')->name('treasureChestList');//宝箱列表
    Route::post('/addTreasure','IconManagementController@treasureCreate');//添加宝箱
    Route::get('/treasureDet/{id}','IconManagementController@treasureDetail')->name('treasureDetail');//宝箱详情
    Route::post('/treasureUp','IconManagementController@treasureUpdate');//修改宝箱
    Route::get('/box/{id}','IconManagementController@boxList')->name('boxList');//宝箱物品列表
    Route::post('/boxAdd','IconManagementController@boxGoodsAdd');//宝箱物品添加
    Route::get('/boxDet/{id}','IconManagementController@boxDetail')->name('boxDetail');//宝箱物品详情
    Route::post('/boxUp','IconManagementController@boxUpdate');//宝箱物品修改
    Route::get('/boxDel/{id}','IconManagementController@boxDelete');//宝箱物品删除

    //物品兑换码管理
    Route::get('/goods_code','IconManagementController@goodsCodeShow')->name('goodsCodeShow');
    Route::post('/goods_code','IconManagementController@goodsCodeMake');//生成兑换码
    Route::get('/codeList','IconManagementController@goodsCodeList')->name('goodsCodeList');
    Route::get('/goodsCodeDel/{id}','IconManagementController@goodsCodeDelete');//删除
    Route::post('/goodsCodeHandle','IconManagementController@goodsCodeHandle');//批量删除

    //APP安装包列表
    Route::get('install_package','GoodsDistributionController@installPackageList')->name('installPackageList');//列表
    Route::get('installPackage','GoodsDistributionController@installPackage')->name('installPackage');//添加页
    Route::post('installPackage','GoodsDistributionController@addInstallPackage');//添加
    Route::get('installPackageDe/{id}','GoodsDistributionController@installPackageDetail')->name('installPackageDetail');//详情
    Route::post('installPackageUp','GoodsDistributionController@installPackageUp');//更新


    Route::get('/substationConfig','SubstationController@substationConfig')->name('substationConfig');
    Route::post('/addSubstation','SubstationController@postAdd')->name('addSubstation');
    Route::get('/deleteSubstation/{id}','SubstationController@deleteSub')->name('deleteSubstation');
    Route::post('/postEditSubstation','SubstationController@editSub')->name('postEditSubstation');
    Route::post('/changeSubstation','SubstationController@changeSubstation')->name('changeSubstation');

    
    Route::get('/vipConfig', 'VipShopController@vipShopConfig')->name('vipConfig');
    Route::get('/vipPackageList', 'VipShopController@vipPackageList')->name('vipPackageList');
    Route::get('/addPackagePage', 'VipShopController@addPackagePage')->name('addPackagePage');
    Route::get('/vipInfoList', 'VipShopController@vipInfoList')->name('vipInfoList');
    Route::get('/vipShopList', 'VipShopController@vipShopList')->name('vipShopList');
    Route::get('/vipShopAuth/{id}', 'VipShopController@vipShopAuth')->name('vipShopAuth');
    Route::get('/vipDetailsList', 'VipShopController@vipDetailsList')->name('vipDetailsList');
    Route::get('/vipDetailsAuth', 'VipShopController@vipDetailsAuth')->name('vipDetailsAuth');

    Route::post('/config/vip', 'VipShopController@vipConfigUpdate')->name('vipConfigUpdate');
    Route::get('/packageStatus/{id}','VipShopController@updatePackageStatus')->name('packageStatusUpdate');
    Route::get('/packageDelete/{id}','VipShopController@packageDelete')->name('packageDelete');
    Route::get('/editPackagePage/{id}','VipShopController@editPackagePage')->name('editPackagePage');
    Route::post('/addPackage','VipShopController@addPackage')->name('addPackage');
    Route::get('/interviewDelete/{id}','VipShopController@interviewDelete')->name('interviewDelete');
    Route::post('/addInterview','VipShopController@addInterview')->name('addInterview');
    Route::get('/editInterviewPage/{id}','VipShopController@editInterviewPage')->name('editInterviewPage');
    Route::post('/editInterview/{id}','VipShopController@editInterview')->name('interviewUpdate');
    Route::post('/endTimeUpdate','VipShopController@endTimeUpdate')->name('endTimeUpdate');
    Route::get('/privilegesDelete/{id}','VipShopController@privilegesDelete')->name('privilegesDelete');
    Route::get('/updateStatus/{id}','VipShopController@updateStatus')->name('statusUpdate');
    Route::get('/updateRecommend/{id}','VipShopController@updateRecommend')->name('recommendUpdate');
    Route::get('/addPrivilegesPage', 'VipShopController@addPrivilegesPage')->name('addPrivilegesPage');
    Route::post('/addPrivileges','VipShopController@addPrivileges')->name('addPrivileges');
    Route::get('/editPrivilegesPage/{id}','VipShopController@editPrivilegesPage')->name('editPrivilegesPage');
    Route::post('/updatePrivileges/{id}','VipShopController@updatePrivileges')->name('privilegesUpdate');
    Route::post('/editPackage/{id}','VipShopController@editPackage')->name('packageUpdate');

    //文学
    Route::get('/bookList','LiteratureController@bookList')->name('bookList');//书籍列表
    Route::get('/bookDetail/{id}','LiteratureController@bookDetail')->name('bookDetail');//书籍详情
    Route::get('/addBook','LiteratureController@createBookShow')->name('createBookShow');//添加书籍页
    Route::post('/addBook','LiteratureController@createBook');//添加书籍
    Route::get('/updateBook/{id}','LiteratureController@updateBookShow')->name('updateBookShow');//修改书籍页
    Route::post('/updateBook','LiteratureController@updateBook');//修改书籍
    //失物招领
    Route::get('/lastAndFound','LastAndFoundController@lastAndFoundList')->name('lastAndFoundList');//失物招领列表
    Route::get('/lastAndFoundDet/{id}','LastAndFoundController@lastAndFoundDetail')->name('lastAndFoundDetail');//失物招领详情
    //论坛
    Route::get('forumList','ForumController@forumListShow')->name('forumListShow');//论坛列表
    Route::get('addForum','ForumController@createForumShow')->name('createForumShow');//发布新帖页
    Route::post('addForum','ForumController@createForum');//发布新帖


});
//apk下载
Route::get('/manage/dowload','ApkDowloadController@apkDowload');