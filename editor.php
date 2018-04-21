<?php include 'codeEditorACE.class.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>codeEditorACE</title>
  
  <!-- development version, includes helpful console warnings -->
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
  .file-list ul{
    padding-left: 10px;
  }
</style>
<div class="columns main-colums">
  <div class="column is-narrow" id="files_control">
    <div style="/*width: 200px;*/">
      <file-list
      :allfiles="allfiles"
      ></file-list>
    </div>
  </div>
  <div class="column editor-column">
    <div id="editor">function foo(items) {
      var x = "All this is syntax highlighted";
      var asd = <? echo $ce->fileList(); ?>;
      return x;
    }</div>
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
    <? /*
    <div class="item field" v-for="file in files">
      <span class="button is-primary">
        <i class="f-icon" :class="[file.extension]"></i>
        {{file.basename}}
      </span>
    </div>
     */ ?>
  </div>
</script>
<script type="text/x-template" id="file-list-item-template">
  <div class="filename" :class="['icon_'+file.extension]">
    <span @click="loadFile">{{file.basename}}</span>
    <span class="tag is-primary" v-if="file.is_dir" @click="loadFolder">+</span>
    <ul v-if="isFolder">
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
    ,methods:{
      loadFolder: function(){
        let thisX = this.file;
        let dataToSend = {
          action : "getFolder"
          ,data : thisX.dirname + thisX.basename + "/"
        }
        
        axios.post('codeEditorACE.class.php', dataToSend )
          .then(function (response) {
            Vue.set(thisX, 'children', response.data);
            //console.log('ok:');
            //console.log(response.data);
          })
          .catch(function (error) {
            //console.log('error:');
            //console.log(error);
          });
      },
      loadFile: function(){
        let thisX = this.file;
        let dataToSend = {
          action : "getFile"
          ,data : thisX.dirname + thisX.basename
        }
        axios.post('codeEditorACE.class.php', dataToSend )
          .then(function (response) {
            editor.setValue(response.data);
            //console.log(response.data);
          })
          .catch(function (error) {
            //console.log('error:');
            //console.log(error);
          });
      }
    }
    ,computed: {
    isFolder: function () {
      return this.file.is_dir/* && this.file.children.length*/;
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
</body>
</html>