<?php

class addHezuquDataToMysql
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
     * 执行事务操作
     * @param array $room
     */
    public function transAction($room = array()){
        if(empty($room)){
            return;
        }
        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try{
            if($this->hasHouseExist($room['id'])){
                $this->addHouseInfo($room);
                if($this->hasUserExist($room['user']['id'])){
                    $this->addUserInfo($room['user']);
                }
                $this->addHouseImage($room['images']);
            }
            $pdo->commit();
        } catch (PDOException $e){
            $pdo->rollBack(); //事物回滚

            die('事物出错：'.$e->getMessage());
        }
    }

    /**
     * 添加房源信息
     * @param $room
     */
    public function addHouseInfo($room)
    {
        if(empty($room)){
            return;
        }
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into hezuqu_houses values(:id,:rent,:readyTime,:room,:parlor,:payment,
                                  :rentType,:buildArea,:listImageUrl,:status,:createTime,:userId,
                                  :communityName,:areaName,:businessAreaName,:hasWIFI,:hasFridge,:hasAirConditioning,
                                  :hasWasher,:hasToilet,:hasShower,:hasWardrobe,:hasHeating,:hasGas,:hasKitchen,
                                  :hasTV,:hasSofa,:hasBalcony,:hasElevator,:hasBed,:description,:subwayName,:toiletCount,
                                  :subwayStationName,:requirement)");
            $stmt->execute(array(
                ":id"=>$room['id'],
                ":rent"=>empty($room['rent']) ? 1000 : $room['rent'],
                ":readyTime"=>$room['readyTime'],
                ":room"=>$room['room'],
                ":parlor"=>$room['parlor'],
                ":payment"=>$room['payment'],
                ":rentType"=>$room['rentType'],
                ":buildArea"=>array_key_exists("buildArea", $room) ? $room['buildArea'] : 0,
                ":listImageUrl"=>$room['listImageUrl'],
                ":status"=>$room['status'],
                ":createTime"=>$room['createTime'],
                ":userId"=>$room['userId'],
                ":communityName"=>$room['communityName'],
                ":areaName"=>$room['areaName'],
                ":businessAreaName"=>$room['businessAreaName'],
                ":hasWIFI"=>(int)$room['hasWIFI'],
                ":hasFridge"=>(int)$room['hasFridge'],
                ":hasAirConditioning"=>(int)$room['hasAirConditioning'],
                ":hasWasher"=>(int)$room['hasWasher'],
                ":hasToilet"=>(int)$room['hasToilet'],
                ":hasShower"=>(int)$room['hasShower'],
                ":hasWardrobe"=>(int)$room['hasWardrobe'],
                ":hasHeating"=>(int)$room['hasHeating'],
                ":hasGas"=>(int)$room['hasGas'],
                ":hasKitchen"=>(int)$room['hasKitchen'],
                ":hasTV"=>(int)$room['hasTV'],
                ":hasSofa"=>(int)$room['hasSofa'],
                ":hasBalcony"=>(int)$room['hasBalcony'],
                ":hasElevator"=>(int)$room['hasElevator'],
                ":hasBed"=>(int)$room['hasBed'],
                ":description"=>$this->removeEmojiChar($room['description']),
                ":subwayName"=>$room['subwayName'],
                ":toiletCount"=>$room['toiletCount'],
                ":subwayStationName"=>array_key_exists("subwayStationName",$room) ? $room['subwayStationName'] : '',
                ":requirement"=>array_key_exists('requirement',$room) ? $room['requirement'] : "",
            ));
        }catch (PDOException $e){
            die("房源数据添加失败:".$e->getMessage());
        }

    }

    /**
     * 添加业主信息
     * @param $user
     */
    public function addUserInfo($user)
    {
        if(empty($user)){
            return;
        }
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into hezuqu_user values(:id,:nickname,:avatar,:gender,:mobileNumber,
                                   :birthday,:openId,:unionId,:pushToken,:pf,:career,:personalProfile,:constellation,
                                   :realName,:certNo,:provincesName,:cityName)");
            $stmt->execute(array(
                ":id"=>$user['id'],
                ":nickname"=>$this->removeEmojiChar($user['nickname']),
                ":avatar"=>$user['avatar'],
                ":gender"=>$user['gender'],
                ":mobileNumber"=>$user['mobileNumber'],
                ":birthday"=>array_key_exists("birthday",$user) ? $user['birthday'] : "",
                ":openId"=>array_key_exists("openId",$user) ? $user['openId'] : "",
                ":unionId"=>array_key_exists("unionId",$user) ? $user['unionId'] : "",
                ":pushToken"=>array_key_exists('pushToken',$user) ? $user['pushToken'] : "",
                ":pf"=>array_key_exists("pf",$user) ? $user['pf'] : '',
                ":career"=>array_key_exists('career',$user) ? $this->removeEmojiChar($user['career']): '',
                ":personalProfile"=>$user['personalProfile'],
                ":constellation"=>array_key_exists('constellation',$user) ? $user['constellation'] : '',
                ":realName"=>array_key_exists("realName", $user) ? $user['realName'] : "",
                ":certNo"=>array_key_exists("certNo", $user) ? $user['certNo'] : "",
                ":provincesName"=>array_key_exists("userProvincesName", $user) ? $user['userProvincesName'] : "",
                ":cityName"=>array_key_exists("userCityName", $user) ? $user['userCityName'] : ""
            ));
        }catch (PDOException $e){
            die("业主数据添加失败:".$e->getMessage());
        }

    }

    /**
     * 添加房源图片
     * @param $images
     */
    public function addHouseImage($images)
    {
        if(empty($images)){
            return;
        }
        $pdo = $this->pdo;
        try{
            foreach ($images as $image){
                $image['id'];
                $stmt = $pdo->prepare("insert into hezuqu_images VALUES(:id,:infoId,:url,:createTime,:type)");
                $stmt->execute(array(
                    ":id"=>$image['id'],
                    ":infoId"=>$image['infoId'],
                    ":url"=>$image['url'],
                    ":createTime"=>$image['createTime'],
                    ":type"=>$image['type']
                ));
            }
        }catch (PDOException $e){
            die("房源图片添加失败:".$e->getMessage());
        }
    }

    /**
     * 判断用户是否已经存在
     * @param $id
     * @return bool
     */
    public function hasUserExist($id)
    {
        $pdo = $this->pdo;
        $stmt = $pdo->query("select id from hezuqu_user where id = '$id'"); //返回一个PDOStatnment对象
        $rows = $stmt->fetchAll(); //获取所有
        if(!empty($rows)){
            return false;
        } else {
            return true;
        }
    }

    /**
     * 判断房源是否已经存在
     * @param $id
     * @return bool
     */
    public function hasHouseExist($id)
    {
        $pdo = $this->pdo;
        $stmt = $pdo->query("select id from hezuqu_houses where id = '$id'"); //返回一个PDOStatnment对象
        $rows = $stmt->fetchAll(); //获取所有
        if(!empty($rows)){
            return false;
        } else {
            return true;
        }
    }

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