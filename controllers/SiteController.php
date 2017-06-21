<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\helpers\Json;

class SiteController extends Controller {
	public $enableCsrfValidation = false;

	public $servername = "localhost";
	public $username = "root";
	public $password = "psw.db7898";
	public $dbname = "answer";
	public $user = 'user';
	public $conn;

	/**
	 * construct function
	 */
// 	function __construct() {
// 	}

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
	 * @return Json
	 */
	public function actionIndex() {
		// 创建连接
		$this->conn = \yii::$app->db;

		// 响应数据格式为json
		Yii::$app->response->format=\yii\web\Response::FORMAT_JSON;

		// 获取url参数
		$action = isset ( $_POST ['action'] ) ? $_POST ['action'] : '';
		$name = isset ( $_POST ['userName'] ) ? $_POST ['userName'] : '';
		$psd = isset ( $_POST ['userPwd'] ) ? $_POST ['userPwd'] : '';
		$psd = md5($psd);


		if ($action === 'login') {
			$this->login ( $name, $psd, true );
		} else if ($action === 'register') {
			$this->register ( $name, $psd);
		} else if ($action === 'modifyPsd') {
			$this->modifyPsd ( $name, $psd);
		} else if ($action === 'showAll') {
			$this->showAll ();
		} else {
			$result = array (
					"result" => "error_request"
			);
			$json = json_encode ( $result );
			echo $json;
		}
	}

	/**
	 * 用户登录
	 * @param
	 * @return Json
	 */
	function login($name, $psd, $normal) {
		$conn = $this->conn;
		$sql = "select userName,userPwd from $this->user";
		$result = $conn->createCommand ( $sql )->queryAll ();

		if ($conn) {
			$sql = "select userId,userName,userPwd from $this->user";
			$result = $conn->createCommand ( $sql )->queryAll ();
			$success = false;
			foreach ( $result as $row ) {
				if ($name == $row ['userName'] && $psd == $row ['userPwd']) {
					$success = true;
					$_SESSION['userId'] = $row['userId'];
					$_SESSION['userName'] = $row['userName'];
				}
			}

			if ($normal) {
				$login_result = array (
						'login_result' => $success
				);
				$json = json_encode ( $login_result );
				echo $json;
			}
		}
		return $success;
	}

	/**
	 * Logout action.
	 *
	 * @return string
	 */
	public function actionLogout() {
		Yii::$app->user->logout ();

		return $this->goHome ();
	}

	/**
	 * 用户注册
	 *
	 * @return Json
	 *
	 */
	function register($name, $psd) {

		$eMail = isset($_POST['eMail']) ? $_POST['eMail']:NULL;
		$registerType = isset($_POST['registerType'])?$_POST['registerType']:null;
		$mobile = isset($_POST['mobile'])?$_POST['mobile']:null;
		$sex = isset($_POST['sex'])?$_POST['sex']:null;
		$conn = $this->conn;
		if ($conn) {

			// 数据库查询
			$sql = "select userName from $this->user";
			$result = $conn->createCommand($sql)->queryAll();
			$exist = false;
			foreach ($result as $row){
				if ($name == $row ['userName']) {
					// 注册失败，用户名已存在;
					$exist = true;
					$register_result = array (
							"register_result" => false,
							"error_code" => 0
					);
					$json = json_encode ( $register_result );
					echo $json;
				}
			}

			// 插入数据库
			if (! $exist) {
				$sql = "insert into $this->user (userName,userPwd,eMail,sex,mobile,registerType) values ('".$name."','".$psd."','".$eMail."','".$sex."','".$mobile."','".$registerType."');";
				$success = $conn->createCommand($sql)->execute();
				if ($success) {
					// 注册成功
					$register_result = array (
							"register_result" => $success
					);
					$json = json_encode ( $register_result );
					echo $json;
				} else {

					// 注册失败，数据库插入错误
					$register_result = array (
							"register_result" => $success,
							"error_code" => 1
					);
					$json = json_encode ( $register_result );
					echo $json;
				}
			}
		} else {
			echo "register0000";
		}
	}
	/**
	 * 修改登录密码
	 * @return json
	 */
	function modifyPsd($name, $psd) {

		$newpsd = isset ( $_POST ['newpsd'] ) ? $_POST ['newpsd'] : NULL;
		$conn = $this->conn;
		if ($conn) {
			// 用户登录
			$login_result = $this->login ( $name, $psd, false );
			// 修改密码
			if ($login_result) {
				$sql = "update $this->user set userPwd=$newpsd where userName=$name";
				$success = $conn->createCommand($sql)->execute();
				if ($success) {
					// 修改成功
					$modify_result = array (
							"modify_result" => $success
					);
					$json = json_encode ( $modify_result );
					echo $json;
				} else {
					// 修改失败，数据库错误
					$modify_result = array (
							"modify_result" => $success,
							"error_code" => 1
					);
					$json = json_encode ( $modify_result );
					echo $json;
				}
			} else {
				// 修改失败，登录失败
				$modify_result = array (
						"modify_result" => false,
						"error_code" => 2
				);
				$json = json_encode ( $modify_result );
				echo $json;
			}
		}
	}

	/**
	 * 显示所有用户
	 * @return json
	 */
	function showAll() {
		$conn = $this->conn;
		if ($conn) {
			$sql = "select * from $this->user";
			$result = $conn->createCommand($sql)->queryAll();
			$success = false;
			$array_data = array ();

			$total = count($result);
			// $data = array("total"=>$total,"datas"=>array(array("data"=>"123","name"=>"zhugeheng"),
			// array("data"=>"456","name"=>"zhaodanni")
			// ));

// 			while ( $row = mysql_fetch_array ( $result ) ) {
			foreach ($result as $row){
				$array_temp = array (
						"name" => $row ['userName'],
						"tel" => $row ['mobile']
				);
				array_push ( $array_data, $array_temp );
			}
			$data = array (
					"total" => $total,
					"datas" => $array_data,
					"result" => true
			);
			$json = json_encode ( $data );
			echo $json;
		}
	}
}
