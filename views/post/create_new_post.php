<div class=" card card-outline card-primary">
    <div class=" card-header">
      <h5 class=" card-title">Create New Post</h5>
    </div>
    <div class="card-footer">
        <form action="./controllers/post_controller.php" method="post" enctype="multipart/form-data">
            <div class=" form-group">
              <label for="">Title</label>
              <input type="text" name="title" max="50" class=" form-control" required  id="">

            </div>
            <div class=" form-group">
              <label for="">Description (introduction summary)</label>
              <textarea name="description" class=" form-control" id="" cols="30" rows="3"></textarea>
            </div>
            <div class=" form-group">
              <label for="">Content</label>

                  <div id="toolbar-container">
                    <span class="ql-formats">
                      <select class="ql-font"></select>
                      <select class="ql-size"></select>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-bold"></button>
                      <button class="ql-italic"></button>
                      <button class="ql-underline"></button>
                      <button class="ql-strike"></button>
                    </span>
                    <span class="ql-formats">
                      <select class="ql-color"></select>
                      <select class="ql-background"></select>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-script" value="sub"></button>
                      <button class="ql-script" value="super"></button>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-header" value="1"></button>
                      <button class="ql-header" value="2"></button>
                      <button class="ql-blockquote"></button>
                      <button class="ql-code-block"></button>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-list" value="ordered"></button>
                      <button class="ql-list" value="bullet"></button>
                      <button class="ql-indent" value="-1"></button>
                      <button class="ql-indent" value="+1"></button>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-direction" value="rtl"></button>
                      <select class="ql-align"></select>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-link"></button>
                      <button class="ql-image"></button>
                      <button class="ql-video"></button>
                      <button class="ql-formula"></button>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-clean"></button>
                    </span>
                  </div>
              <div id="editor">

              </div>
            </div>
            <div class=" form-group">
              <Textarea name="content" hidden class=" form-control content" id=""></Textarea>
            </div>
            <div class=" form-group">
              <label for="">Attach File</label>
              <input type="file" name="file" id="" class=" form-control">
            </div>
            <div class=" card-body">
                <button type="submit" class=" btn btn-info btn-block" name="create_post">Create Post</button>
            </div>
        </form>
    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
 const quill = new Quill('#editor', {
    modules: {
      syntax: true,
      toolbar: '#toolbar-container',
    },
    placeholder: 'Type something here...',
    theme: 'snow',
  });
  quill.on('text-change', function() {
  // Update textarea with Quill HTML
  //document.querySelector(".content").value = quill.root.innerHTML;
  const plainText = quill.root.innerHTML;
    console.log(plainText);
  $(".content").val(plainText);
});

    
</script>