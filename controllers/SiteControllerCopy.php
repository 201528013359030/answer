<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
	public $servername = "localhost";
	public $username = "root";
	public $password = "psw.db7898";
	public $dbname = "answer";
	public $user = 'user';
	public $conn;

	/**
	 * construct function
	 */
	function __construct()
	{


	}


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
    	// 创建连接
    	$this->$conn = new mysqli($servername, $username, $password, $dbname);

    	//获取url参数
    	$action = isset($_POST['action'])?$_POST['action']:0;
    	$name = isset($_POST['name'])?$_POST['name']:0;
    	$psd = isset($_POST['psd'])?$_POST['psd']:0;
    	$mobile = isset($_POST['mobile'])?$_POST['mobile']:0;
    	$newpsd = isset($_POST['newpsd'])?$_POST['newpsd']:0;

    	if($action=='login') {
    		$this->actionLogin($name, $psd, true);
    	} else if($action=='register') {
    		register($name, $psd,$mobile);
    	} else if($action=='modifyPsd') {
    		modifyPsd($name, $psd,$newpsd);
    	} else if($action=='showAll') {
    		showAll();
    	} else {
    		$result = array("result"=>"error_request");
    		$json = json_encode($result);
    		echo $json;
    	}

      //关闭连接
    function close_conn() {

        mysql_close($this->$conn);
    }

    	$db = \Yii::$app->db;

    	$sql = "select * from $this->user";
    	$result = $db->createCommand($sql)
    		->queryAll();

//     	$return = array(
//     			"message"=>"Hello World!",
//     			"name"=>"方业全"
//     	);
		$return = json_encode($result);
		return $return;
//     	return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin($name,$psd,$normal)
    {

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Register action.
     *
     * @return
     */
    public function actionRegister($name,$psd,$mobile=0)
    {


    	if($conn) {

    		// 选择数据库
    		mysql_select_db($this->conn, $this->dbname);
    		//数据库查询
    		$result = mysql_query("select userName from $this->user");
    		$exist = false;
    		while($row = mysql_fetch_array($result)) {
    			if($name == $row['userName']) {
    				//注册失败，用户名已存在;
    				$exist = true;
    				$register_result = array("register_result"=>false,"error_code"=>0);
    				$json = json_encode($register_result);
    				echo $json;
    			}
    		}

    		//插入数据库
    		if(!$exist) {

    			$id = mysql_num_rows($result) + 1;
    			$success = mysql_query("insert into $this->dbname ('userName','userPwd') values($name,  $psd)");
    			if($success) {
    				//注册成功
    				$register_result = array("register_result"=>$success);
    				$json = json_encode($register_result);
    				echo $json;
    			} else {

    				//注册失败，数据库插入错误
    				$register_result = array("register_result"=>$success,"error_code"=>1);
    				$json = json_encode($register_result);
    				echo $json;
    			}
    		}
    	}else{
    		echo "register0000";
    	}
    }
    /**
     * 修改登录密码
     *
     */
    function modifyPsd($name, $psd,$newpsd) {

    	global $conn;

    	if($conn) {
    		//用户登录
    		$login_result = login($name, $psd, false);
    		//修改密码
    		if($login_result) {
    			$success = mysql_query("update $this->user set userPwd='$newpsd' where userName='$name'");
    			if($success) {
    				//修改成功
    				$modify_result = array("modify_result"=>$success);
    				$json = json_encode($modify_result);
    				echo $json;
    			} else {
    				//修改失败，数据库错误
    				$modify_result = array("modify_result"=>$success,"error_code"=>1);
    				$json = json_encode($modify_result);
    				echo $json;
    			}
    		} else {
    			//修改失败，登录失败
    			$modify_result = array("modify_result"=>false,"error_code"=>2);
    			$json = json_encode($modify_result);
    			echo $json;
    		}
    	}
    }


    /**
     * 显示所有用户
     *
     */
    function showAll() {
    	global $conn;
    	if($conn) {
    		$result = mysql_query("select * from $this->user");
    		$success = false;
    		$array_data = array();

    		$total = mysql_num_rows($result);
    		//$data = array("total"=>$total,"datas"=>array(array("data"=>"123","name"=>"zhugeheng"),
    		//                                                                                   array("data"=>"456","name"=>"zhaodanni")
    		//                                                                      ));

    		while($row = mysql_fetch_array($result)) {
    			$array_temp = array("name"=>$row['uerName'], "tel"=>$row['mobile']);
    			array_push($array_data, $array_temp);
    		}
    		$data = array("total"=>$total,"datas"=>$array_data, "result"=>true);
    		$json = json_encode($data);
    		echo $json;

    	}
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
