<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller {
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
	 * Displays homepage.
	 *
	 * @return string
	 */
	public function actionIndex() {
		// 创建连接
// 		$this->conn = new \mysqli($this->servername, $this->username, $this->password, $this->dbname );
		$this->conn = \yii::$app->db;

		// 获取url参数
		$action = isset ( $_POST ['action'] ) ? $_POST ['action'] : 0;
		$name = isset ( $_POST ['name'] ) ? $_POST ['userName'] : 0;
		$psd = isset ( $_POST ['psd'] ) ? $_POST ['userPwd'] : 0;


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

		// 关闭连接
		function close_conn() {
			mysql_close ( $this->$conn );
		}

// 		$db = \Yii::$app->db;

// 		$sql = "select * from $this->user";
// 		$result = $db->createCommand ( $sql )->queryAll ();

// 		// $return = array(
// 		// "message"=>"Hello World!",
// 		// "name"=>"方业全"
// 		// );
// 		$return = json_encode ( $result );
// 		return $return;
	}

	/**
	 * 用户登录
	 *
	 * @return string
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
	 * @return
	 *
	 */
	public function register($name, $psd, $mobile = 0) {

		$mobile = isset ( $_POST ['mobile'] ) ? $_POST ['mobile'] : 0;
		$eMail = isset($_POST['eMail']) ? $_POST['eMail']:0;
		$registerType = isset($_POST['registerType'])?$_POST['registerType']:0;
		$mobile = isset($_POST['mobile'])?$_POST['mobile']:0;
		$sex = isset($_POST['sex'])?$_POST['sex']:0;
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

				$sql = "insert into $this->user ('userName','userPwd','eMail','sex',mobile','registerType') values($name,  $psd, $eMail,$sex,$mobile,$registerType)";
				$success = $conn->createCommand($sql)->queryAll();
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
	 */
	function modifyPsd($name, $psd) {

		$newpsd = isset ( $_POST ['newpsd'] ) ? $_POST ['newpsd'] : 0;
		$conn = $this->conn;
		if ($conn) {
			// 用户登录
			$login_result = $this->login ( $name, $psd, false );
			// 修改密码
			if ($login_result) {
				$sql = "update $this->user set userPwd='$newpsd' where userName='$name'";
				$success = $conn->createCommand($sql)->queryAll();
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
						"name" => $row ['uerName'],
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

	/**
	 * Displays contact page.
	 *
	 * @return string
	 */
	public function actionContact() {
		$model = new ContactForm ();
		if ($model->load ( Yii::$app->request->post () ) && $model->contact ( Yii::$app->params ['adminEmail'] )) {
			Yii::$app->session->setFlash ( 'contactFormSubmitted' );

			return $this->refresh ();
		}
		return $this->render ( 'contact', [
				'model' => $model
		] );
	}

	/**
	 * Displays about page.
	 *
	 * @return string
	 */
	public function actionAbout() {
		return $this->render ( 'about' );
	}
}
