<?php
class QueryArticle extends connect
{
    private $article;

    const THUMBS_WIDTH = 200; // サムネイルの幅

    public function __construct()
    {
        parent::__construct();
    }

    public function setArticle(Article $article)
    {
        $this->article = $article;
    }

    // 画像アップロード
    private function saveFile($old_name)
    {
        $new_name = date('YmdHis') . mt_rand();

        if ($type = exif_imagetype($old_name)) {
            // 元画像の縦横サイズを取得
            list($width, $height) = getimagesize($old_name);

            // サムネイルの比率を求める
            $rate = self::THUMBS_WIDTH / $width;  // 比率
            $thumbs_height = $rate * $height;

            // キャンバス作成
            $canvas = imagecreatetruecolor(self::THUMBS_WIDTH, $thumbs_height);

            switch ($type) {
                case IMAGETYPE_JPEG:
                    $new_name .= '.jpg';

                    // サムネイルを保存
                    $image = imagecreatefromjpeg($old_name);
                    imagecopyresampled($canvas, $image, 0, 0, 0, 0, self::THUMBS_WIDTH, $thumbs_height, $width, $height);
                    imagejpeg($canvas, __DIR__ . '/../album/thumbs-' . $new_name);
                    break;

                case IMAGETYPE_GIF:
                    $new_name .= '.gif';

                    // サムネイルを保存
                    $image = imagecreatefromgif($old_name);
                    imagecopyresampled($canvas, $image, 0, 0, 0, 0, self::THUMBS_WIDTH, $thumbs_height, $width, $height);
                    imagegif($canvas, __DIR__ . '/../album/thumbs-' . $new_name);
                    break;

                case IMAGETYPE_PNG:
                    $new_name .= '.png';

                    // サムネイルを保存
                    $image = imagecreatefrompng($old_name);
                    imagecopyresampled($canvas, $image, 0, 0, 0, 0, self::THUMBS_WIDTH, $thumbs_height, $width, $height);
                    imagepng($canvas, __DIR__ . '/../album/thumbs-' . $new_name);
                    break;

                default:
                    // JPEG・GIF・PNG以外の画像なら処理しない
                    imagedestroy($canvas);
                    return null;
            }
            imagedestroy($canvas);
            imagedestroy($image);

            // 元サイズの画像をアップロード
            move_uploaded_file($old_name, __DIR__ . '/../album/' . $new_name);

            // 保存したファイル名を返す
            return $new_name;
        } else {
            // 画像以外なら処理しない
            return null;
        }
    }

    public function save()
    {
        // bindParam用
        $title = $this->article->getTitle();
        $body = $this->article->getBody();
        $filename = $this->article->getFilename();

        if ($this->article->getId()) {
            // IDがあるときは上書き
            $id = $this->article->getId();
            if ($file = $this->article->getFile()) {
                // ファイルが既にある場合、古いファイルを削除する
                if ($this->article->getFilename()) {
                    unlink(__DIR__ . '/../album/thumbs-' . $this->article->getFilename());
                    unlink(__DIR__ . '/../album/' . $this->article->getFilename());
                }
                // 新しいファイルのアップロード
                $this->article->setFilename($this->saveFile($file['tmp_name']));
                $filename = $this->article->getFilename();
            }
            $stmt = $this->dbh->prepare("UPDATE articles
              SET title=:title, body=:body, filename=:filename, updated_at=NOW()
              WHERE id=:id");

            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':body', $body, PDO::PARAM_STR);
            $stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // IDがなければ新規作成

            if ($file = $this->article->getFile()) {
                $this->article->setFilename($this->saveFile($file['tmp_name']));
                $filename = $this->article->getFilename();
            }

            $stmt = $this->dbh->prepare("INSERT INTO articles (title, body, filename, created_at, updated_at)
            VALUES (:title, :body, :filename, NOW(), NOW())");
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':body', $body, PDO::PARAM_STR);
            $stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    public function find($id)
    {
        $stmt = $this->dbh->prepare("SELECT * FROM articles WHERE id=:id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $article = null;
        if ($result) {
            $article = new Article();
            $article->setId($result['id']);
            $article->setTitle($result['title']);
            $article->setBody($result['body']);
            $article->setFilename($result['filename']);
            $article->setCreatedAt($result['created_at']);
            $article->setUpdatedAt($result['updated_at']);
        }
        return $article;
    }

    public function findAll()
    {
        $stmt = $this->dbh->prepare("SELECT * FROM articles");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $articles = array();
        foreach ($results as $result) {
            $article = new Article();
            $article->setId($result['id']);
            $article->setTitle($result['title']);
            $article->setBody($result['body']);
            $article->setFilename($result['filename']);
            $article->setCreatedAt($result['created_at']);
            $article->setUpdatedAt($result['updated_at']);
            $articles[] = $article;
        }
        return $articles;
    }
}
