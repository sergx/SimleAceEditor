<?php $time_start = microtime(true); ?>

<?php include 'simpleAceEditor.class.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>SimpleAceEditor</title>
  <link rel="stylesheet" href="/css/font-awesome.min.css">
  <!-- development version, includes helpful console warnings -->
  <script src="//cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
  <script src="//unpkg.com/axios/dist/axios.min.js"></script>
  <script src="//cloud9ide.github.io/emmet-core/emmet.js"></script>
  <script src="js/require.js" type="text/javascript" charset="utf-8"></script>
  <!-- <script src="ace/lib/ace/ace.js" type="text/javascript" charset="utf-8"></script> -->
  <!-- <script src="ace-builds/src/ext-modelist.js" type="text/javascript" charset="utf-8"></script> -->
  <!-- <script src="ace-builds/src/ext-emmet.js" type="text/javascript" charset="utf-8"></script> -->
  
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
   - ВебВоркером следить за тем, не изменился ли файл. К примеру другим разработчиком он мог отредактироваться. То же - просто папки и другие файлы. Типа если изменился файл, то он как-то помечается - меняется цвет тега с размером файла.
   - При нажитии на tag с кол-вом файлов - перезагрузить этот элемент.
   - Заархивировать, скачать
   - Заказать, разархивировать
   - ? вкладки...
  -->
  
</head>
<body>
  <script>
    var editor;
    var modelist/* = ace.require("ace/ext/modelist")*/;
    require.config({paths: { "ace" : "ace/lib/ace"}});
  </script>
  <!-- load ace emmet extension -->
  <style>
  
  </style>
<div class="columns main-colums is-marginless" id="files_control">
  <div class="column is-narrow is-paddingless">
    <file-list
    :allfiles="allfiles"
    :apstatus="apstatus"
    @changeselected="changeselected"
    ></file-list>
  </div>
  <div class="column is-paddingless editor-column">
    <div class="editor_title">
      <div class="field is-grouped">
        <div class="control field has-addons is-expanded">
          <p class="control">
            <a class="button is-static">
              {{apstatus.activeFile.dirname}}
            </a>
          </p>
          <p class="control is-expanded">
            <input class="input" type="text" v-model.lazy="apstatus.activeFile.basename">
          </p>
        </div>
        <p class="control">
          <span class="button is-success" @click="saveFile">
            <span class="icon is-small">
              <i class="fa fa-check"></i>
            </span>
            <span>Save</span>
          </span>
          <!-- is-loading -->
          <a class="button is-success" title="Disabled button" disabled>Download</a>
          <a :href="['/'+apstatus.activeFile.dirname+apstatus.activeFile.basename]"  target="_blank" class="button is-success" title="Disabled button">Open</a>
        </p>
      </div>
    </div>
    <div id="editor">let life = "good";</div>
  </div>
</div>

<template id="file-list-template">
  <div class="file-list">
    <ul>
      <li v-for="(file, key, index)  in allfiles">
        <file-list-item
          :file="file"
          :key="index"
          :allfiles="allfiles"
          :apstatus="apstatus"
          @changeselected="changeselected"
        ></file-list-item>
      </li>
    </ul>
  </div>
</template>

<template id="file-list-item-template">
  <div :class="['icon_'+file.extension]">
    <div class="filename" @click="handleClick" :class="{active:isOpen, is_folder:isFolder, is_file:!isFolder}">
      <span class="file_basename tag" :class="{'is-primary':isOpen,'is-white':!isOpen && !fileInEditor, 'is-info':fileInEditor}">
        <i class="fa fa-caret-right" v-if="isFolder"  :class="{'fa-rotate-90':isOpen}"></i>
        {{file.basename}} <!--{{selected}} {{_uid}} {{fileInEditor}}-->
        <i class="fa fa-pencil" v-if="fileInEditor"></i>
      </span>
      <span class="tag is-light" v-if="childrenCount !== undefined">{{childrenCount}}</span>
      <span class="tag is-light" v-if="!isFolder">{{file.filesize}}</span>
    </div>
    <ul v-if="isOpen">
      <li v-for="(file, key, index) in file.children">
        <file-list-item
          :file="file"
          :key="index"
          :allfiles="allfiles"
          :apstatus="apstatus"
          @changeselected="changeselected"
        ></file-list-item>
      </li>
    </ul>
  </div>
</template>



<script>
  Vue.component('file-list', {
    template: '#file-list-template'
    ,props: [
      'allfiles'
      ,'apstatus'
    ]
    //,data: function () {
    //  return {
    //    selected: undefined
    //  }
    //}
    ,methods:{
      changeselected: function(apstatus){
        this.$emit('changeselected', apstatus);
      }
    }
  });

  Vue.component('file-list-item', {
    template: '#file-list-item-template'
    ,props: [
      'file'
      ,'allfiles'
      ,'apstatus'
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
      ,childrenCount: function () {
        return this.file.children && this.file.children.length;
      }
      ,fileInEditor: function(){
        return this.apstatus.selected === this._uid;
      }
    }
    ,watch:{
      'apstatus.activeFile.basename': function(newV, oldV){
        console.log(this.apstatus.activeFile.dirname+newV);
        
      }
    }
    ,methods:{
      myTest:function(){
        console.log(this.apstatus);
      }
      ,
      handleClick: function(){
        if(this.isFolder){
          return this.loadFolder();
        }else{
          return this.loadFile();
        }
      },
      loadFolder: function(){
        let thisX = this;
        if(thisX.childrenCount || thisX.childrenCount === 0 && thisX.isOpen){
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
            Сохранить файл по Ctrl+S
            Отобразить статус сохранен ли файл, или нет (свет кнопки, или обводки)
            Если начали редактировать файл, и не сохранили его, то при переключении на другой файл выводить предупреждение.
        */
        
        let thisX = this;
        let dataToSend = {
          action : "getFile"
          ,data : thisX.file.dirname + thisX.file.basename
        }
        axios.post('simpleAceEditor.class.php', dataToSend )
          .then(function (response) {
            let apstatus = {activeFile:thisX.file,selected:thisX._uid};
            
            let mode = modelist.getModeForPath(dataToSend.data);
            editor.session.setMode(mode.mode);
            editor.setValue(response.data);
            thisX.$emit('changeselected', apstatus);
            thisX.myTest();
          })
          .catch(function (error) {});
        
      }
      ,renameFile: function(){
        
      }
      /*
      ,deleteFile: function(){
        
      }
      ,copyFile: function(){
        
      }
      ,saveAsFile: function(){
        
      }
      */
      ,changeselected:function(apstatus){
        this.$emit('changeselected', apstatus);
      }
    }
  });
  

  var vm = new Vue({
    el: "#files_control"
    ,data: {
      allfiles: <? echo $ce->fileList(); ?>,
      apstatus:{
        activeFile: {
          dirname: "",
          basename: ""
        },
        selected: undefined
      }

    }
    //,computed: {
    //  activeFile: function(){
    //    return {
    //      dirname: this.apstatus.activeFile.dirname,
    //      basename: this.apstatus.activeFile.basename
    //    }
    //  }
    //}
    ,mounted () {
      // https://ace.c9.io/demo/autoresize.html
      // https://github.com/ajaxorg/ace/wiki/Configuring-Ace
      //var aceU;
      
      
      require(["ace/ace", "ace/ext/emmet", "ace/ext/modelist"], function(ace) {
        editor = ace.edit("editor", {
          wrapBehavioursEnabled: true,
          wrap: true,
          tabSize:2
        });
        editor.setTheme("ace/theme/chrome");
        editor.session.setMode("ace/mode/html");
        editor.setOption("enableEmmet", true);
        (function () {
            modelist = ace.require("ace/ext/modelist");
            // the file path could come from an xmlhttp request, a drop event,
            // or any other scriptable file loading process.
            // Extensions could consume the modelist and use it to dynamically
            // set the editor mode. Webmasters could use it in their scripts
            // for site specific purposes as well.
            var filePath = "blahblah/weee/some.js";
            var mode = modelist.getModeForPath(filePath).mode;
            console.log(mode);
            editor.session.setMode(mode);
        }());
      });
    }
    ,methods: {
      saveFile: function(){
        if(!this.apstatus.activeFile.basename.length){
          console.log("Нечего сохранять.");
        }
        let thisX = this;
        //console.log(editor.getValue());
        
        axios.post(
          'simpleAceEditor.class.php',
          {
            action: "saveFile",
            data: {
              filename: thisX.apstatus.activeFile.dirname+thisX.apstatus.activeFile.basename ,
              content: editor.getValue()
            }
          }
        )
          .then(function (response) {
            let apstatus = {activeFile:thisX.file,selected:thisX._uid};
            let mode = modelist.getModeForPath(dataToSend.data.filename);
            editor.session.setMode(mode.mode);
            thisX.$emit('changeselected', apstatus);
            thisX.myTest();
            console.log(response.data);
          })
          .catch(function (error) {});
          
        //console.log(editor.getValue());
      }
      ,changeselected: function(apstatus){
        this.apstatus = apstatus;
      }
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