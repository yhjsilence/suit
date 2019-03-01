<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/1/21
 * Time: 11:53
 */

class selectHezuquDataToSuit
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

    public function transAction($house,$images,$user){
        $pdo = $this->pdo;
        try{
            $pdo->beginTransaction();
            $this->insertDataToSuit($house);
            $this->addDataToFitment($house);
            $this->addUserType($house['id']);
            $this->addHouseImages($images,$house['userId']);
            if(!empty($user)){
                $this->addUserInfoToSuit($user);
            }
            $pdo->commit();
        }catch (PDOException $e){
            $pdo->rollBack();
            die("事物出错：".$e->getMessage());
        }
    }

    /**
     * 从合租趣获取数据hezuqu_houses
     * @return mixed
     */
    public function getHousesFromHezuqu()
    {
        $pdo = $this->pdo;
        $stmt = $pdo->query("select * from hezuqu_houses where room <= 3 and rent > 1000 and description <> '' order by id desc"); //返回一个PDOStatnment对象
        $rows = $stmt->fetchAll(); //获取所有
        return $rows;
    }

    /**
     * 通过房源id获取房源图片hezuqu_images
     * @param $houseId
     * @return mixed
     */
    public function getHouseImagesById($houseId){
        $pdo = $this->pdo;
        $stmt = $pdo->query("select * from hezuqu_images where infoId = ".$houseId); //返回一个PDOStatnment对象
        $rows = $stmt->fetchAll(); //获取所有
        return $rows;
    }

    /**
     * 通过用户id获取用户信息hezuqu_user
     * @param $userId
     * @return mixed
     */
    public function getUserInfoById($userId){
        $pdo = $this->pdo;
        //1.判断user_info表中是否已经存在user
        $st = $pdo->query("select id from user_info where id = ".$userId);
        $row = $st->fetch();
        if(empty($row)){
            $stmt = $pdo->query("select * from hezuqu_user where id = ".$userId);
            $rows = $stmt->fetch();
            return $rows;
        } else {
            return "";
        }
    }

    /**
     * 将数据添加人house_info表中
     */
    public function insertDataToSuit($house)
    {
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into house_info VALUES(:id, :user_id, :apply_id, :ward, :village, :road, 
                                    :door_num, :metro, :mall, :type, :area, :style, :tfloor, :floor, :face, :elevator, :pet, :adult, 
                                    :children, :share, :describes, :rent, :pay_way, :visit_time, :status, :zhonghe, 
                                    :suit, :payment, :easy, :time, :userMax, :masterId, :origin, :authentic)");
            $stmt->execute($res = array(
                ":id" => $house['id'],
                ":user_id" => $house['userId'],
                ":apply_id" => 0,
                ":ward" => $this->getWard($house['areaName']),
                ":village" => $house['communityName'],
                ":road" => "",
                ":door_num" => "",
                ":metro" => $this->getMetro($house['subwayName'],$house['subwayStationName']),
                ":mall" => $house['businessAreaName'],
                ":type" => $this->getType($house['room'], $house['parlor'], $house['toiletCount']),
                ":area" => $house['buildArea'],
                ":style" => 0,
                ":tfloor" => 0,
                ":floor" => 0,
                ":face" => "",
                ":elevator" => $house['hasElevator'],
                ":pet" => 1,
                ":adult" => 10,
                ":children" => 10,
                ":share" => 2,
                ":describes" => $this->removeEmojiChar($house['description']),
                ":rent" => $house['rent'],
                ":pay_way" => $this->getPayWay($house['rentType'],$house['payment']),
                ":visit_time" => "",
                ":status" => 0,
                ":zhonghe" => 0,
                ":suit" => 0,
                ":payment" => 0,
                ":easy" => 0,
                ":time" => date("Y-m-d H:i:s", substr($house['createTime'],0,10)),
                ":userMax" => 0,
                ":masterId" => 7,
                ":origin" => '合租趣',
                ":authentic" => 0,
            ));
        }catch (PDOException $e){
            die("house_info添加失败:".$e->getMessage());
        }
    }

    /**
     * 添加设施表fitment
     * @param $fitment
     */
    public function addDataToFitment($fitment)
    {
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into fitment VALUES(:id, :house_id, :balcony, :bed, :bedstand, :chair, :cooker,
                                   :desk, :garden, :hearth, :kongt, :locker, :mattres, :fridge, :sofa, :toilet, :tv,
                                   :cupboard, :washer, :water, :wifi, :fit_other)");
            $stmt->execute(array(
                ":id" => null,
                ":house_id" => $fitment['id'],
                ":balcony" => $fitment['hasBalcony'],
                ":bed" => $fitment['hasBed'],
                ":bedstand" => 0,
                ":chair" => 0,
                ":cooker" => $fitment['hasKitchen'],
                ":desk" => 0,
                ":garden" => 0,
                ":hearth" => $fitment['hasGas'],
                ":kongt" => $fitment['hasAirConditioning'],
                ":locker" => 0,
                ":mattres" => 0,
                ":fridge" => $fitment['hasFridge'],
                ":sofa" => $fitment['hasSofa'],
                ":toilet" => $fitment['hasToilet'],
                ":tv" => $fitment['hasTV'],
                ":cupboard" => $fitment['hasWardrobe'],
                ":washer" => $fitment['hasWasher'],
                ":water" => $fitment['hasHeating'],
                ":wifi" => $fitment['hasWIFI'],
                ":fit_other" => 0,
            ));

        }catch (PDOException $e){
            die("fitment添加失败:".$e->getMessage());
        }
    }

    /**
     * 添加用户类型表user_type
     * @param $houseId
     */
    public function addUserType($houseId){
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
     * 添加房源图片house_img
     * @param $images
     */
    public function addHouseImages($images,$userId){
        $pdo = $this->pdo;
        try{
            foreach ($images as $image){
                $stmt = $pdo->prepare("insert into house_img VALUES(:id,:user_id,:house_id,:image)");
                $stmt->execute(array(
                    ":id"=>$image['id'],
                    ":user_id"=>$userId,
                    ":house_id"=>$image['infoId'],
                    ":image"=>$image['url'],
                ));
            }
        }catch (PDOException $e){
            die("house_img添加失败:".$e->getMessage());
        }
    }


    /**
     * 添加用户信息user_info
     * @param $user
     */
    public function addUserInfoToSuit($user = array()){
        if(empty($user)){
            return;
        }
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into user_info VALUES (:id, :name, :caller, :phone, :avatar, :sex, :province, 
                                  :city, :openId, :identity, :status, :birthday,:career,:constellation)");
            $stmt->execute(array(
                ":id" => $user['id'],
                ":name" => $user['nickname'],
                ":caller" => $user['realName'],
                ":phone" => $user['mobileNumber'],
                ":avatar" => $user['avatar'],
                ":sex" => $this->getGender($user['gender']),
                ":province" => $user['provincesName'],
                ":city" => $user['cityName'],
                ":openId" => $user['openId'],
                ":identity" => 0,
                ":status" => 0,
                ":birthday" => $user['birthday'],
                ":career" => $user['career'],
                ":constellation" => $user['constellation'],
            ));

        }catch (PDOException $e){
            die("user_info添加失败:".$e->getMessage());
        }
    }


    /**
     * 处理ward字段
     * @param $areaName
     * @return string
     */
    private function getWard($areaName){
        if("浦东"==$areaName){
            $areaName .= "新区";
        } else {
            $areaName .= "区";
        }
        return $areaName;
    }


    /**
     * 处理字段metro
     * @param $subwayName
     * @param $subwayStationName
     * @return string
     */
    private function getMetro($subwayName,$subwayStationName){
        if(empty($subwayName) || empty($subwayStationName)){
            return "";
        }
        $subway = explode(" ", $subwayName);
        $station = explode(" ", $subwayStationName);
        return $subway[0].$station[0];
    }

    /**
     * 处理字段type
     * @param $room
     * @param $parlor
     * @param $toiletCount
     * @return string
     */
    private function getType($room, $parlor, $toiletCount)
    {
        $str = "";
        if( 0 != $room){
            $str .= $room."室";
        }
        if( 0 != $parlor){
            $str .= $parlor."厅";
        }
        if( 0 != $toiletCount){
            $str .= $toiletCount."卫";
        }
        return $str;
    }

    /**
     * 处理字段pay_way
     * @param $rentType
     * @param $payment
     * @return string
     */
    private function getPayWay($rentType,$payment){
        return ($rentType-1).($payment-1).",押".$rentType."付".$payment;
    }

    /**
     * 处理字段sex
     * @param $gender
     * @return int
     */
    private function getGender($gender){
        $sex = 0;
        if(1 == $gender){
            $sex = 1;
        } else if(2 == $gender){
            $sex = 0;
        } else {
            $sex = 2;
        }
        return $sex;
    }

    /**
     * 处理emoji表情
     * @param $str
     * @return string
     */
    function removeEmojiChar($str)
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