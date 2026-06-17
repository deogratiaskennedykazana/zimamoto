<div class=" card card-info">
    <div class=" card-header"> <h4 class=" card-title">Create Min subsidiary</h4> </div>
    <form action="./controllers/min_sub_controllers.php" method="post" class=" was-validated">
        <div class=" card-body">
            <div class=" form-group">
                <label for="" class=" form-label">Min Sub Name</label>
                <input type="text" name="name" required class=" form-control " id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label">Select Subsidiary</label>
                <select name="subsidiary" required class=" form-control select2-form select2bs4-form" id="">
                    <option value="">Select Below</option>
                    <?php
                        $subsidiaries = getAllSubsidiaries($conn);
                        if($subsidiaries && is_array($subsidiaries)){
                            foreach($subsidiaries as $result){
                                ?>
                                <option value="<?= $result['id'] ?>"><?= $result['name'] ?></option>
                                <?php
                            }
                        }
                    ?>
                </select>
            </div>
            <div class=" form-group">
                <label for="" class=" form-label">Min Sub Category</label>
                <select name="category" required class=" select2-form select2bs4-form form-control" id="">
                    <option value="">Select Below</option>
                    <option value="stock">Stock</option>
                    <option value="asset">asset</option>
                    <option value="others">others</option>
                    <option value="amana">amana </option>
                    <option value="share">share</option>
                    <option value="saving">saving</option>
                    <option value="loan">loan</option>
                </select>
            </div>
            <div class=" form-group">
                <label for="" class=" form-label">Type</label>
                <select name="type" required class=" form-control select2-form select2bs4-form" id="">
                    <option value="">Select Below</option>
                    <option value="person">person</option>
                    <option value="company">company</option>
                    <option value="others">Others</option>
                </select>
            </div>
        </div>
        <div class=" card-footer">
            <button type="submit" class=" btn btn-primary btn-block btn-sm" name="registerminsub">Register Min Sub</button>
        </div>
    </form>
</div>