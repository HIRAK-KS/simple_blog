<?php
class QueryCategory extends connect
{
    private $category;

    public function __construct()
    {
        parent::__construct();
    }

    public function setCategory(Category $category)
    {
        $this->category = $category;
    }

    public function save()
    {
        $name = $this->category->getName();

        // 新規登録
        $stmt = $this->dbh->prepare("INSERT INTO categories(name) VALUES (:name)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function findAll()
    {
        $stmt = $this->dbh->prepare("SELECT * FROM categories");
        $stmt->execute();
        $categories = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $result) {
            $category = new Category();
            $category->setId($result['id']);
            $category->setName($result['name']);
            $categories[$result['id']] = $category;
        }
        return $categories;
    }
}

class Category
{
    private $id = null;
    private $name = null;

    public function save()
    {
        $queryCategory = new QueryCategory();
        $queryCategory->setCategory($this);
        $queryCategory->save();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
