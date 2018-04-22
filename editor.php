<?php $time_start = microtime(true); ?>

<?php include 'simpleAceEditor.class.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>SimpleAceEditor</title>
  
  <!-- development version, includes helpful console warnings -->
  <script defer src="https://use.fontawesome.com/releases/v5.0.7/js/all.js"></script>
  <script src="//cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
  <script src="//unpkg.com/axios/dist/axios.min.js"></script>
  <script src="ace-builds/src-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>
  
  <link rel="stylesheet" href="bulma/css/bulma.css">
  <link rel="stylesheet" href="<? echo $ce->fileCasheFix('css/style.css'); ?>">
  
  <!--
  Задачи проекта:
  
  Бэкенд:
   - получаем список файлов корневой дерриктории JSON
   - При нажатии на папку - подгружаем новые файлы
   - Сохранение файла
   
  Фронт
   - Сформировать список файлов, с выделением папок
   - При нажатии на папку либо загружается с сервера информация, либо просто открывается список содержимого
   - Если открыт файл, то подгружаются папки до этого файла
   - При нажатии на файл - подгружается файл в Редактор
   - Кнопки действия: Сохранить / (Открыть в новом окне / скачать / Сохранить как ..)
   
  Фишки:
   - ВебВоркером следить за тем, не изменился ли файл. К примеру другим разработчиком он мог отредактироваться.
  
  -->
  
</head>
<body>
<style>

</style>
<div class="columns main-colums is-marginless">
  <div class="column is-narrow is-paddingless" id="files_control">
    <file-list
    :allfiles="allfiles"
    ></file-list>
  </div>
  <div class="column is-paddingless editor-column">
    <div class="editor_title">
      <div class="field is-grouped">
        <p class="control is-expanded" style="padding-top: 0.375em;">
          <span class="title">...</span>
        </p>
        <p class="control">
          <a class="button is-success">
            <span class="icon is-small">
              <i class="fas fa-check"></i>
            </span>
            <span>Сохранить</span>
          </a>
          <!-- is-loading -->
          <a class="button is-success" title="Disabled button" disabled>Скачать</a>
        </p>
      </div>


    </div>
    <div id="editor">let life = "good";</div>
  </div>
</div>

<script type="text/x-template" id="file-list-template">
  <div class="file-list">
    <ul>
      <li v-for="file in allfiles">
        <file-list-item
        :file="file"
        :allfiles="allfiles"
        ></file-list-item>
      </li>
    </ul>
  </div>
</script>
<script type="text/x-template" id="file-list-item-template">
  <div :class="['icon_'+file.extension]">
    <div class="filename" @click="handleClick" :class="{active:isOpen, is_folder:isFolder}">
      <i class="fas fa-caret-right" v-if="isFolder"></i>
      <span class="file_basename" ><i class="far" :class="fileIconClass" style='color:#7789a0'></i> {{file.basename}}</span>
    </div>
    <ul v-if="isOpen">
      <li v-for="(file, index) in file.children">
        <file-list-item
        :file="file"
        :allfiles="allfiles"
        ></file-list-item>
      </li>
    </ul>
  </div>
</script>

<script>
  // https://ace.c9.io/demo/autoresize.html
  // https://github.com/ajaxorg/ace/wiki/Configuring-Ace
  var editor = ace.edit("editor", {
    wrapBehavioursEnabled: true
    ,wrap: true
    ,tabSize:2
  });
  editor.setTheme("ace/theme/chrome");
  editor.session.setMode("ace/mode/javascript");
</script>

<script>
  Vue.component('file-list', {
    template: '#file-list-template'
    ,props: [
      'allfiles'
    ]
  });

  Vue.component('file-list-item', {
    template: '#file-list-item-template'
    ,props: [
      'file'
      ,'allfiles'
    ]
    ,data: function () {
      return {
        isOpen: false
      }
    }
    ,computed: {
      // computed обновляются только когда их "формерователи" изменяются
      isFolder: function () {
        return this.file.is_dir;
      }
      ,hasChildren: function () {
        return this.file.children && this.file.children.length;
      }
      ,fileIconClass: function(){
        let className;
        console.log(this.file.extension);
        if(this.isFolder){
            className = "fa-folder";
            return className;
        }
        switch(this.file.extension){
          case "php":
          case "js":
          case "css":
            className = "fa-file-code";
            break;
          default:
            className = "fa-file";
            break;
        }
        return className;
        
      }
      //,isOpenC: function () {
      //  return this.file.isOpen;
      //}
    }
    ,methods:{
      handleClick: function(){
        if(this.isFolder){
          return this.loadFolder();
        }else{
          return this.loadFile();
        }
      },
      loadFolder: function(){
        /* TODO:
            OK Проверить - стоит ли загружать файлы, т.к. они могут быть уже загружены
            OK Скрыть или открыть папку
        */
        
        let thisX = this;
        if(thisX.hasChildren){
          // Если уже загружен список файлов для папки
          thisX.isOpen = !thisX.isOpen;
        }else{
          axios.post(
              'simpleAceEditor.class.php',
              {
                action : "getFolder",
                data : thisX.file.dirname + thisX.file.basename + "/"
              }
            ).then(function (response) {
              Vue.set(thisX.file, 'children', response.data);
              Vue.set(thisX, 'isOpen', true);
            }).catch(function (error) {});
        }
      },
      loadFile: function(){
        /* TODO:
            Фильтровать файлы разного типа. Загружить только текстовые.
            Выводить предупреждение, если это неизвестный тип файла, или файл слишком большой
            
            Изменять тип подсветки
            Сохранить файл по Ctrl+S
            Отобразить статус сохранен ли файл, или нет (свет кнопки, или обводки)
        */
        
        let thisX = this.file;
        let dataToSend = {
          action : "getFile"
          ,data : thisX.dirname + thisX.basename
        }
        axios.post('simpleAceEditor.class.php', dataToSend )
          .then(function (response) {
            editor.setValue(response.data);
          })
          .catch(function (error) {});
      }
    }
  });
  

  var vm = new Vue({
    el: "#files_control"
    ,data: {
      allfiles: <? echo $ce->fileList(); ?>
    }
  });
</script>
<script>
  console.info(

'%c <?php 
  $time_total = intval((microtime(true) - $time_start)*10000,10)/10000;
  echo "PHP: ".$time_total." сек";
?> ', 'background:#91e8b0;font-weight:bold;'
    );
</script>
</body>
</html>