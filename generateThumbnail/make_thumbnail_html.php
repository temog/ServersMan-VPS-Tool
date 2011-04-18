<?php

$generate = new GenerateThumbnailHTML(
    '/var/serversman/htdocs/MyStorage/android_pic',
    '/android_pic',
    '/var/serversman/htdocs/MyStorage/android_pic/index.html',
    '/var/serversman/htdocs/MyStorage/android_pic/thumbnail',
    '/android_pic/thumbnail',
    '100',
    '600'
);
$generate->generate();

class GenerateThumbnailHTML {

    //画像設置場所(iPhone とか Android から画像をUpする先)
    protected $imgPath;
    //画像設置場所(URL)
    protected $imgURL;
    //HTML出力先
    protected $htmlPath;
    //サムネイル設置場所（ディレクトリ）
    protected $thumbPath;
    //サムネイル設置場所（URL）
    protected $thumbURL;
    //サムネイル画像の横幅(px)
    protected $thumbWidth;
    //画像の最大横幅(px) 省略時はリサイズしない
    protected $imgWidth;

    //HTMLに出力するサムネイル個数
    protected $numOfThumb = 5;
    //対応する拡張子
    private $ext = array('.jpg', '.gif', '.png');
    //画像一覧
    protected $images = array();
    //thumbnail画像一覧
    protected $thumbnails = array();
    //html生成フラグ
    private $updateHTML = false;
    //画像の親タグ(div)のCSSクラス名
    protected $cssName = 'serversman_pictures';

    public function __construct($imgPath, $imgURL, $htmlPath,
            $thumbPath, $thumbURL, $thumbWidth, $imgWidth = null){
        $this->imgPath = $imgPath;
        $this->imgURL = $imgURL;
        $this->htmlPath = $htmlPath;
        $this->thumbPath = $thumbPath;
        $this->thumbURL = $thumbURL;
        $this->thumbWidth = $thumbWidth;
        $this->imgWidth = $imgWidth;
    }

    public function generate(){
        //画像設置場所から画像一覧取得
        $this->getImgPathPictures();

        //thumbnail 設置場所から画像一覧取得
        $this->getThumbPathPictures();

        //画像一覧とthumbnail一覧を比較して存在しない画像があったらthumbnail 用画像を作る
        $this->generateThumbImages();

        //画像一覧になく、thumnailに存在する画像を削除
        $this->deleteThumbImages();

        //画像生成した場合、html を生成する
        $this->generateHTML();

    }

    //画像設置場所から画像一覧取得
    private function getImgPathPictures(){
        if(! $open = opendir($this->imgPath)){
            return;
        }

        while(false !== ($file = readdir($open))){
            $filePath = $this->imgPath . "/" . $file;
            if(is_dir($filePath)){
                continue;
            }
            //拡張子チェック
            foreach($this->ext as $ext){
                if(preg_match("/" . $ext . "/e", strtolower($file))){
                    $this->images[filemtime($filePath) . "_" . $file] = array($file, $ext);
                    continue;
                }
            }
        }
        closedir($open);

        //画像更新日（降順）で並び替え
        krsort($this->images);
    }

    //thumbnail 設置場所から画像一覧取得
    private function getThumbPathPictures(){
        if(! $open = opendir($this->thumbPath)){
            return;
        }

        while(false !== ($file = readdir($open))){
            $filePath = $this->thumbPath . "/" . $file;
            if(is_dir($filePath)){
                continue;
            }
            array_push($this->thumbnails, $file);
        }
        closedir($open);
    }

    //画像一覧とthumbnail一覧を比較して存在しない画像があったらthumbnail 用画像を作る
    private function generateThumbImages(){
        foreach($this->images as $thumbName => $fileAndExt){
            if(in_array($thumbName, $this->thumbnails)){
                continue;
            }
            $this->updateHTML = true;
            $file = $fileAndExt[0];
            $ext = $fileAndExt[1];

            //thumbnail作成
            switch($ext){
                case ".jpg":
                    $img = imagecreatefromjpeg($this->imgPath . "/" . $file);
                    break;
                case ".png":
                    $img = imagecreatefrompng($this->imgPath . "/" . $file);
                    break;
                case ".gif":
                    $img = imagecreatefromgif($this->imgPath . "/" . $file);
                    break;
                default:
                    $this->stderr("invalid extension");
            }

            //画像サイズ取得
            $width = ImageSX($img);
            $height = ImageSY($img);
            $thumbWidth = $this->thumbWidth;
            $thumbHeight = 0;

            //thumbnail 指定サイズより大きければthumbnail作成
            if($width > $thumbWidth){
                $thumbHeight = ($thumbWidth / $width) * $height;
                $thumbImg = imagecreatetruecolor($thumbWidth, $thumbHeight);
                imagecopyresampled($thumbImg, $img, 0, 0, 0, 0,
                    $thumbWidth, $thumbHeight, $width, $height);
            }

            //thumbnail 配置
            $saveThumbPath = $this->thumbPath . "/" . $thumbName;

            if($thumbHeight == 0 && $ext == ".jpg"){
                imagejpeg($img, $saveThumbPath, 90);
            } else if($thumbHeight == 0 && $ext == ".png"){
                imagepng($img, $saveThumbPath);
            } else if($thumbHeight == 0 && $ext == ".gif"){
                imagegif($img, $saveThumbPath);
            } else if($ext == ".jpg"){
                imagejpeg($thumbImg, $saveThumbPath, 90);
            } else if($ext == ".png"){
                imagepng($thumbImg, $saveThumbPath);
            } else if($ext == ".gif"){
                imagegif($thumbImg, $saveThumbPath);
            }

            //画像が最大幅を超えてたらリサイズ
            if($this->imgWidth && $width > $this->imgWidth){
                $resizeHeight = ($this->imgWidth / $width) * $height;
                $resizeImg = imagecreatetruecolor($this->imgWidth, $resizeHeight);
                imagecopyresampled($resizeImg, $img, 0, 0, 0, 0,
                    $this->imgWidth, $resizeHeight, $width, $height);

                unlink($this->imgPath . "/" . $file);

                switch($ext){
                    case ".jpg":
                        imagejpeg($resizeImg, $this->imgPath . "/" . $file, 90);
                        break;
                    case ".png":
                        imagepng($resizeImg, $this->imgPath . "/" . $file);
                        break;
                    case ".gif":
                        imagegif($resizeImg, $this->imgPath . "/" . $file);
                        break;
                    default:
                        $this->stderr("invalid extension");
                }
            }

            //メモリ解放
            if($thumbHeight != 0){
                imagedestroy($thumbImg);
            }
            imagedestroy($img);

            $this->stdout("generage thumbnail " . $saveThumbPath);
        }
    }

    //画像一覧になく、thumnailに存在する画像を削除
    private function deleteThumbImages(){
        foreach($this->thumbnails as $i => $thumbName){
            $exist = false;
            foreach($this->images as $k_thumbName => $fileAndExt){
                if($thumbName == $k_thumbName){
                    $exist = true;
                    break;
                }
            }

            //存在しなければ thumbnail を削除
            if($exist){
                continue;
            }
            $this->updateHTML = true;
            unlink($this->thumbPath . "/" . $thumbName);
            array_splice($this->thumbnails, $i, 1);

            $this->stdout("delete thumbnail " . $this->thumbPath . "/" . $thumbName);
        }
    }

    //画像生成 or thumbnail 削除した場合、html を生成する
    private function generateHTML(){
        if(! $this->updateHTML){
            return;
        }

        $i = 0;
        if(! $f = fopen($this->htmlPath, "w")){
            $this->stderr("fopen error " . $this->htmlPath);
        }
        if(! flock($f, LOCK_EX)){
            $this->stderr("file lock error " . $this->htmlPath);
        }
        fwrite($f, '<div class="' . $this->cssName . '">');
        foreach($this->images as $thumbName => $fileAndExt){
            $i++;
            $file = $fileAndExt[0];
            fwrite($f, '<a href="' . $this->imgURL . '/' . $file . '">'.
                '<img src="' . $this->thumbURL . '/' . $thumbName . '"></a>');

            if($i == $this->numOfThumb){
                break;
            }
        }
        fwrite($f, '</div>');
        flock($f, LOCK_UN);
        fclose($f);

        $this->stdout("generate html " . $this->htmlPath);
    }

    //表示するThumbnail 個数を変更
    public function setNumOfThumb($num){
        if(! preg_match("/^[0-9]+$/", $num)){
            return false;
        }
        $this->numOfThumb = $num;
        return true;
    }

    //divタグのCSS名を変更
    public function setCssName($cssName){
        if(! $cssName){
            return false;
        }
        $this->cssName = $cssName;
    }

    //stderr用
    private function stderr($message){
        $message = "[" . date("Y-m-d H:i:s") . "] " . $message;
        fwrite(STDERR, $message . "\n");
        exit;
    }

    //stdout用
    private function stdout($message){
        $message = "[" . date("Y-m-d H:i:s") . "] " . $message;
        echo $message . "\n";
    }
}

