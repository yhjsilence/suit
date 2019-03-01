<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/1/21
 * Time: 14:40
 */

class selectNuanDataToSuit
{
    private $host = '39.96.196.222';
    private $user = 'root';
    private $password = '123456';
    private $dbname = 'My_suit';
    private $pdo;

    //私有的静态变量
    private static $_instance = null;

    private function __construct()
    {

        $this->pdo = $this->PDOinit();
    }

    public static function getInstance(){
        if(is_null(self::$_instance)){
            return self::$_instance = new self();
        }
        return self::$_instance;

    }

    private function setConfig()
    {
        $db = array(
            'host' => $this->host,         //设置服务器地址
            'port' => '3306',              //设端口
            'dbname' => $this->dbname,             //设置数据库名
            'username' => $this->user,           //设置账号
            'password' => $this->password,      //设置密码
            'charset' => 'utf8',             //设置编码格式
            'dsn' => 'mysql:host='.$this->host.';dbname='.$this->dbname.';port=3306;charset=utf8',   //这里不知道为什么，也需要这样再写一遍。
        );

        return $db;
    }

    private function PDOinit(){
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //默认是PDO::ERRMODE_SILENT，0，（忽略错误模式）
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //默认是PDO::FETCH_BOTH, 4
        );
        $db = $this->setConfig();
        try{
            $pdo = new PDO($db['dsn'],$db['username'],$db['password'],$options);
        }catch (PDOException $e){
            die("数据库连接失败：".$e->getMessage());
        }
        //或者更通用的设置属性方式：
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //设置异常处理方式
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); //设置默认关联索引遍历

        return $pdo;
    }

    /**
     * 事务操作
     * @param $house
     */
    public function transAction($house){
        $pdo = $this->pdo;
        try{
            $pdo->beginTransaction();

            if($this->houseExist($house)){
                $res = $this->getUserIdByInfo($house['author']);
                if(empty($res)){
                    $userId = $this->addDataToUserInfo($house['author']);
                } else {
                    $userId = $res['id'];
                }
                $houseId = $this->addDataToHouseInfo($house,$userId);
                $this->addDataToUserType($houseId);
                $this->addDataToFitment($houseId);
                $this->addDataToHouseImg($houseId,$userId,$house['images']);
            }

            $pdo->commit();
        }catch (PDOException $e){
            $pdo->rollBack();
            die("事物出错：".$e->getMessage());
        }
    }

    /**
     * 判断房源是否已经存在
     * @param $house
     * @return bool
     */
    public function houseExist($house){
        $pdo = $this->pdo;
        try{
            $fetch = $pdo->query("select id from house_info where describes = '".$this->removeEmojiChar($house['description'])."'");
            $res = $fetch->fetch();
        }catch(PDOException $e){
            die('houseExist:'.$e->getMessage());
        }
        if(empty($res)){
            return true;
        } else {
            return false;
        }
    }


    /**
     * 添加数据到user_info
     * @param $user
     * @return string
     */
    public function addDataToUserInfo($user){
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into user_info VALUES (:id,:name,:caller, :phone, :avatar, :sex, :province, 
                                  :city, :openId, :identity, :status,:birthday,:career,:constellation)");
            $stmt->execute(array(
                ":id" => null,
                ":name" => $user['name'],
                ":caller" => $user['name'],
                ":phone" => null,
                ":avatar" => "https://nuan.io".$user['avatar']['location'],
                ":sex" => 2,
                ":province" => '',
                ":city" => '',
                ":openId" => '',
                ":identity" => 0,
                ":status" => 0,
                ":birthday" => '',
                ":career" => '',
                ":constellation" => ''
            ));
            $id = $pdo->lastInsertId();
        }catch (PDOException $e){
            die("user_info数据添加失败：".$e->getMessage());
        }
        return $id;
    }

    /**
     * 判断用户是否已经存在
     * @param $user
     * @return mixed
     */
    public function getUserIdByInfo($user){
        $pdo = $this->pdo;
        try{
            $fetch = $pdo->query("select id from user_info where name = '".$user['name']."' and avatar = '".$user['avatar']['location']."'");
            $id = $fetch->fetch();
        }catch (PDOException $e){
            die("getUserIdByInfo出错：".$e->getMessage());
        }
        return $id;
    }

    /**
     * 添加数据到
     * @param $house
     * @param $userId
     * @return string
     */
    public function addDataToHouseInfo($house,$userId){
        $pdo = $this->pdo;
        try{
            $stmt =$pdo->prepare("insert into house_info VALUES(:id, :user_id, :apply_id, :ward, :village, :road, 
                                    :door_num, :metro, :mall, :type, :area, :style, :tfloor, :floor, :face, :elevator, :pet, :adult, 
                                    :children, :share, :describes, :rent, :pay_way, :visit_time, :status, :zhonghe, 
                                    :suit, :payment, :easy, :time, :userMax, :masterId,:origin, :authentic)");
            $row = $stmt->execute(array(
                ":id" => null,
                ":user_id" => $userId,
                ":apply_id" => 0,
                ":ward" => array_key_exists('district', $house) ? $house['district'] : '',
                ":village" => array_key_exists('community',$house) ? $house['community'] : '',
                ":road" => '',
                ":door_num" => "",
                ":metro" => '',
                ":mall" => '',
                ":type" => $this->getType(array_key_exists('roomType',$house) ? $house['roomType'] : $house['shortRoomType']),
                ":area" => array_key_exists('size',$house) ? $house['size'] : '',
                ":style" => 0,
                ":tfloor" => array_key_exists('floorTotal', $house) ? $house['floorTotal'] : 0,
                ":floor" => array_key_exists('floorNum', $house) ? $house['floorNum'] : 0,
                ":face" => "",
                ":elevator" => 0,
                ":pet" => 1,
                ":adult" => 10,
                ":children" => 10,
                ":share" => $this->getShare($house['rentType']),
                ":describes" => $this->removeEmojiChar($house['description']),
                ":rent" => array_key_exists("price",$house) ? $house['price'] : 0,
                ":pay_way" => '00,押一付一',
                ":visit_time" => "",
                ":status" => 0,
                ":zhonghe" => 0,
                ":suit" => 0,
                ":payment" => 0,
                ":easy" => 0,
                ":time" => date("Y-m-d H:i:s", strtotime($house['postTime'])),
                ":userMax" => 0,
                ":masterId" => 7,
                ":origin" => "暖房",
                ":authentic" => 0,
            ));
            $id = $pdo->lastInsertId();
        }catch (PDOException $e){
            die("house_info数据添加失败：".$e->getMessage());
        }
        return $id;
    }

    /**
     * 添加用户类型到user_type
     * @param $houseId
     */
    public function addDataToUserType($houseId){
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into user_type VALUES(:id,:house_id, :single, :couple, :family, :friend,:user_other)");
            $stmt->execute(array(
                ":id" => null,
                ":house_id" => $houseId,
                ":single" => 1,
                ":couple" => 1,
                ":family" => 1,
                ":friend" => 1,
                ":user_other" => 1,

            ));

        }catch (PDOException $e){
            die("user_type添加失败:".$e->getMessage());
        }
    }

    /**
     * 添加设施到fitment表
     * @param $houseId
     */
    public function addDataToFitment($houseId){
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into fitment VALUES(:id, :house_id, :balcony, :bed, :bedstand, :chair, :cooker,
                                   :desk, :garden, :hearth, :kongt, :locker, :mattres, :fridge, :sofa, :toilet, :tv,
                                   :cupboard, :washer, :water, :wifi, :fit_other)");
            $stmt->execute(array(
                ":id" => null,
                ":house_id" => $houseId,
                ":balcony" => 0,
                ":bed" => 1,
                ":bedstand" => 0,
                ":chair" => 0,
                ":cooker" => 0,
                ":desk" => 0,
                ":garden" => 0,
                ":hearth" => 0,
                ":kongt" => 0,
                ":locker" => 0,
                ":mattres" => 0,
                ":fridge" => 0,
                ":sofa" => 0,
                ":toilet" => 1,
                ":tv" => 0,
                ":cupboard" => 0,
                ":washer" => 1,
                ":water" => 1,
                ":wifi" => 0,
                ":fit_other" => 1,
            ));

        }catch (PDOException $e){
            die("fitment数据添加失败：".$e->getMessage());
        }
    }

    /**
     * 添加房源图片到house_img
     * @param $houseId
     * @param $userId
     * @param $images
     */
    public function addDataToHouseImg($houseId,$userId,$images){
        $pdo = $this->pdo;
        try{
            foreach ($images as $image){
                $stmt = $pdo->prepare("insert into house_img VALUES(:id,:user_id,:house_id,:image)");
                $stmt->execute(array(
                    ":id"=>null,
                    ":user_id"=>$userId,
                    ":house_id"=>$houseId,
                    ":image"=>$image['location'],
                ));
            }
        }catch (PDOException $e){
            die("house_img添加失败:".$e->getMessage());
        }
    }

    /**
     * 处理字段type
     * @param $type
     * @return string
     */
    private function getType($type){
        if(empty($type)){
            return '一室一卫';
        }
        $count = strlen($type);
        $str = '';
        switch ($type[0]){
            case 1: $str .= '一室';
                break;
            case 2: $str .= '二室';
                break;
            case 3: $str .= '三室';
                break;
            case 4: $str .= '四室';
            default:$str .= '一室';
        }
        if($count>4){
            switch ($type[4]){
                case 1: $str .= '一厅';
                    break;
                case 2: $str .= '二厅';
                    break;
            }
        }
        if($count>8){
            switch ($type[8]){
                case 1: $str .= '一卫';
                    break;
                case 2: $str .= '二卫';
                    break;
            }
        }
        return $str;
    }

    /**
     * 处理字段share
     * @param $share
     * @return int
     */
    private function getShare($share){
        if("shared" == $share){
            return 1;
        }else if("entire" == $share){
            return 0;
        }
        return 2;
    }

    /**
     * 处理emoji表情
     * @param $str
     * @return string
     */
    private function removeEmojiChar($str)
    {
        $mbLen = mb_strlen($str);

        $strArr = [];
        for ($i = 0; $i < $mbLen; $i++) {
            $mbSubstr = mb_substr($str, $i, 1, 'utf-8');
            if (strlen($mbSubstr) >= 4) {
                continue;
            }
            $strArr[] = $mbSubstr;
        }

        return implode('', $strArr);
    }

}