<?php

  class dsc {
    private $sMainKey = '';
    private $db = false;
    private $db_name = '';
    private $db_password = '';
    private $db_user = '';
    private $db_host = '';
    public $iPage = 0;
    
    /*  $formArray
        Liste des champs
    */
    public $formArray = array(
        'sTitle' => array('type' => 'text', 'sLabel' => 'Titre', 'help' => '', 'default' => '','required' => true),
        'sResume' => array('type' => 'text', 'sLabel' => 'Résumé', 'help' => '', 'default' => '','required' => true),
        'sContent' => array('type' => 'textareaPlus', 'sLabel' => 'Contenue', 'help' => '', 'default' => '','required' => true),
        'sImageUrl' => array('type' => 'text', 'sLabel' => 'Image' , 'help' => '', 'default' => '','required' => false),
        'iStatus' => array('type' => 'checkbox', 'sLabel' => 'Publié', 'help' => '', 'default' => 1,'required' => false)
      );
      
      /*  logout
          Déconnexion
      */
      public function logout() {
        setcookie("accessKey",false);
        header('Location: ?p=0');
      }
      
      /*  login
          Formulaire de connexion
      */
      public function login() {
        echo '<form method="POST"><input type="password" name="sMainKey"><input type="submit" value="login"></form>';
      }

      public function __construct() {
        // Authentification
        if(isset($_POST)) { if(isset($_POST['sMainKey'])) { setcookie("accessKey",$_POST['sMainKey']); header('Location: ?p=0'); } }
        // Déconnexion et API
        if(isset($_GET)) { if(isset($_GET['g'])) { $this->getPublicListApi(); return; } if(isset($_GET['logout'])) { $this->logout(); } }
        // Connexion via cookie
        if(!isset($_COOKIE["accessKey"])) {  $this->login(); return; } else { if($_COOKIE["accessKey"] !== $this->sMainKey) { $this->login(); return; } }
        // DB
        $this->db_connect();
        
        if(isset($_GET)) {
          if(isset($_GET['d'])) {
            $sSql = "UPDATE pages SET iStatus=2 WHERE iNoPage=".$_GET['d'];
            $this->db->query($sSql);
            header('Location: ?p=0');   
          }
          if(isset($_GET['p'])) {
            $this->iPage = $_GET['p'];
          }
        }
        $sHtml = $this->getHeader();
        if($this->iPage == 0) {
          $sHtml .= $this->listPages();
        } else {
          $sHtml .= $this->edit($this->iPage);
        }
        $sHtml.= $this->getFooter();
        echo $sHtml;
      }
      
      public function getPublicListApi() {
        $arrData = $this->getPublicList();
        $arrReturn = array();
        foreach ($arrData as $key => $value) {
          $arrReturn[$value['iNoPage']] = array(
            'sTitle' => base64_decode($value['sTitle']),
            'sResume' => base64_decode($value['sResume']),
            'sContent' => base64_decode($value['sContent']),
            'iNoPage' => $value['iNoPage'],
            'dDate' => $value['dDate'],
            'sImageUrl' => $value['sImageUrl']
          );
        }
        echo json_encode($arrReturn);
      }
      
      public function getPublicList() {
        $query = "SELECT * FROM pages WHERE iStatus = 1 ORDER by dDate LIMIT 10";
        $result = $this->db->query($query);
        $rows = array();
        while($row = $result->fetch_array())
        {
          $rows[$row['iNoPage']] = $row;
        }
        return $rows;
      }
      public function getHeader() {
        $sHtml = <<<HTMLRENDER
  <html>
    <head>
      <meta charset="UTF-8">
      <link rel="stylesheet" href="https://bootswatch.com/paper/bootstrap.min.css">
      <title>DSC</title>
    </head>
    <body>
      <form method="POST">          
          <nav class="navbar navbar-fixed-top" style="position: relative;">
            <div class="container-fluid">
              <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                  <span class="sr-only">Toggle navigation</span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="?">Damn Small Cms</a>
              </div>
              <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right" style="margin-right:0px;">
                  <li>
HTMLRENDER;
  if($this->iPage) {
    $arrPage = $this->getList()[$this->iPage];
    $sStatus = ($arrPage['iStatus']?'checked':'');
    $sHtml .= <<<HTMLRENDER
    </li>
    <li>
    <p class="navbar-text"><input type="checkbox" name="iStatus" id="iStatus" class="" value="1" {$sStatus}> Publié</p>
    </li>
    <li>
      <div class="btn-group" style="margin-right:10px;">
        <button type="submit" class="btn btn-primary navbar-btn"><span class="glyphicon glyphicon-floppy-save" aria-hidden="true"></span> Enregistrer</button>
        <button type="button" class="btn btn-primary dropdown-toggle navbar-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          &nbsp;<span class="caret"></span>&nbsp;
          <span class="sr-only">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu">
          <li>
            <a href="?d={$this->iPage}"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Supprimer</a>
          </li>
        </ul>
      </div>
HTMLRENDER;
  } else {
        $sHtml .= <<<HTMLRENDER
            <button type="submit" class="btn btn-success navbar-btn" style="margin-right:10px;"><span class="glyphicon glyphicon-send" aria-hidden="true"></span> Créer un article</button>
HTMLRENDER;
  }
        $sHtml .= <<<HTMLRENDER
                  </li>
                  <li>
                    <button onclick='window.location="?logout=now"' class="btn btn-danger navbar-btn"><span class="glyphicon glyphicon-log-out" aria-hidden="true"></span> Déconnexion</button>
                  </li>
                </ul>
              </div>
            </div>
          </nav>
          <div class="container-fluid">
            <div class="row">
                <div class="col-md-2">
                <p>
                  <a href="?p=0" class="btn btn-block btn-primary"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Ajouter un  article</a>
                </p>
                <div class="list-group">
HTMLRENDER;
                $arrList = $this->getList();
                $sHtml .= '<ul class="list-group">';
                if($arrList) {
                  foreach ($arrList as $key => $value) {
                    $sHtml .= ' <a href="?p='.$value['iNoPage'].'" class="list-group-item"><strong>'.base64_decode($value['sTitle']).'</strong><br />'.$value['dDate'].'</a>';
                  }
                }
                $sHtml .= '</ul>';
        $sHtml .= <<<HTMLRENDER

                  </div>
                </div>
              <div class="col-md-10">
HTMLRENDER;
          return $sHtml;
      }
      
      public function getFooter() {
        return <<<HTMLRENDER
          </div></div></form></div>
          <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
          <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
          <script src="//cdn.tinymce.com/4/tinymce.min.js"></script>
          <script>tinymce.init({ selector:'.tinymceAuto' });</script>
        </body>
  </html>

HTMLRENDER;
      }
      
      public function edit($iPage = 0) {
        if(isset($_POST) && !empty($_POST)) {
          $bValid = true; 
          foreach ($this->formArray as $key => $values) {
            if($values['required']) {
              if(!isset($_POST[$key])) {
                  $bValid = false;
              } else {
                if($_POST[$key] == '') {
                  $bValid = false;
                }
              }
            }
          }
          if($bValid) {
            $arrItem = array();
            foreach ($_POST as $key => $value) {
              if($key == 'iStatus' || $key=='sImageUrl') {
                $arrItem[] = "iStatus=1";
              } elseif($key !== 'iNoPage') {
                $arrItem[] = $key."='".base64_encode($value)."'";
              }
            }
            if(!isset($_POST['iStatus'])) {
              $arrItem[] = "iStatus=0";
            }
            $sItem = implode(',',$arrItem);
            
            $sSql = "UPDATE pages SET ".$sItem." WHERE iNoPage=".$iPage;
            try {
              $this->db->query($sSql);
            }
            catch(PDOException $e) {
              echo $sql . "<br>" . $e->getMessage();
            }
            $sHtml .= '<p>Enregistrer avec succes</p>';
          } else {
            $sHtml .= 'Des champs sont obligatoire';
          }
        }
        // get page data
        $arrPage = $this->getList()[$iPage];
        $sHtml .= '<input type="hidden" name="iNoPage" value="'.$iPage.'">';
        foreach ($arrPage as $key => $value) {
          $sName = $key;
          $arrOptions = $this->formArray[$key];
          switch ($this->formArray[$key]['type']) {
            case 'text':
              $sHtml .= '<div class="form-group"><label for="'.$sName.'">'.$arrOptions['sLabel'].($arrOptions['required']?' (requis)':'').'</label><input type="text" name="'.$sName.'" id="'.$sName.'" class="form-control" value="'.($key=='sImageUrl'?$value:base64_decode($value)).'"></div>';
              break;
            case 'textarea':
              $sHtml .= '<div class="form-group"><label for="'.$sName.'">'.$arrOptions['sLabel'].($arrOptions['required']?' (requis)':'').'</label><textarea name="'.$sName.'" id="'.$sName.'" class="form-control">'.base64_decode($value).'</textarea></div>';
              break;
            case 'textareaPlus':
                $sHtml .= '<div class="form-group"><label for="'.$sName.'">'.$arrOptions['sLabel'].($arrOptions['required']?' (requis)':'').'</label><textarea name="'.$sName.'" id="'.$sName.'" class="tinymceAuto">'.base64_decode($value).'</textarea></div>';
                break;
            case 'checkbox':
            //    $sHtml .= '<div class="form-group"><input type="checkbox" name="'.$sName.'" id="'.$sName.'" class="" value="1" '.($value==1?'checked':'').'> '.$arrOptions['sLabel'].'</div>';
                break;
            default:
              # code...
              break;
          }
        }
        return $sHtml;
      }
      
      public function listPages() {
        $sHtml = '';
        if(isset($_POST) && !empty($_POST)) {
          $bValid = true; 
          foreach ($this->formArray as $key => $values) {
            if($values['required']) {
              if(!isset($_POST[$key])) {
                  $bValid = false;
              } else {
                if($_POST[$key] == '') {
                  $bValid = false;
                }
              }
            }
          }
          if($bValid) {
            $arrItem = array();
            $arrValue = array();
            foreach ($_POST as $key => $value) {
              $arrItem[] = $key;
              $arrValue[] = "'".($key=='sImageUrl'?$value:base64_encode($value))."'";
            }
            $sItem = implode(',',$arrItem);
            $sValue = implode(',',$arrValue);
            $sSql = "INSERT INTO pages (".$sItem.")  VALUES (".$sValue.")";
            try {
              $this->db->query($sSql);
            }
            catch(PDOException $e) {
              echo $sql . "<br>" . $e->getMessage();
            }
            $sHtml .= '<p>Ajouté avec succes</p>';
          } else {
            $sHtml .= 'Des champs sont obligatoire';
          }
        }

        foreach ($this->formArray as $sName => $arrOptions) {
          switch ($arrOptions['type']) {
            case 'text':
              $sHtml .= '<div class="form-group"><label for="'.$sName.'">'.$arrOptions['sLabel'].($arrOptions['required']?' (requis)':'').'</label><input type="text" name="'.$sName.'" id="'.$sName.'" class="form-control" value="'.$arrOptions['default'].'"></div>';
              break;
            case 'textarea':
              $sHtml .= '<div class="form-group"><label for="'.$sName.'">'.$arrOptions['sLabel'].($arrOptions['required']?' (requis)':'').'</label><textarea name="'.$sName.'" id="'.$sName.'" class="form-control">'.$arrOptions['default'].'</textarea></div>';
              break;
            case 'textareaPlus':
                $sHtml .= '<div class="form-group"><label for="'.$sName.'">'.$arrOptions['sLabel'].($arrOptions['required']?' (requis)':'').'</label><textarea name="'.$sName.'" id="'.$sName.'" class="tinymceAuto">'.$arrOptions['default'].'</textarea></div>';
                break;
            default:
              # code...
              break;
          }
        }
        
        $this->db_close();
        return $sHtml;
      }
      
      public function getList() {
        $query = "SELECT * FROM pages WHERE iStatus != 2 ORDER by dDate";
        $result = $this->db->query($query);
        $rows = array();
        while($row = $result->fetch_array())
        {
          $rows[$row['iNoPage']] = $row;
        }
        return $rows;
      }
      
      private function db_connect() {
        $this->db = new mysqli($this->db_host, $this->db_user, $this->db_password, $this->db_name);
        if ($this->db->connect_errno) {
            exit("Echec lors de la connexion à MySQL : (" . $this->db->connect_errno . ") " . $this->db->connect_error);
        }
      }
      
      private function db_close() {
        $this->db->close();
      }
    }
    $dsc = new dsc;
  
