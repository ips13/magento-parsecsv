<?php include_once('functions.php'); ?>
<html>
	<head>
		<link rel="stylesheet" href="assets/bootstrap.min.css">
		<script type="text/javascript" src="assets/jquery.min.js"></script>
	</head>
	<body>
		<?php if(!isset($_REQUEST['direct'])): ?>
			<form name="" class="col-md-4" method="POST" id="csvfiles">
				<h2>Step 1: Files</h2>
				<div class="row">
					<div class="form-group col-md-12">
						<select name="store" class="form-control mb-2">
							<option value="">Select Store</option>
							<option value="us">US</option>
							<option value="int">International</option>
						</select>
					</div>
					<div class="form-group col-md-6">
					 	<label>US</label>
						<?php echo allFiles('catalogfiles[us]','Catalog'); ?>
						<?php echo allFiles('stockfiles[us]','Stock'); ?>
					</div>
					<div class="form-group col-md-6">
						<label>Internatioanl Stock</label>
						<?php echo allFiles('catalogfiles[int]','Catalog'); ?>
						<?php echo allFiles('stockfiles[int]','Stock'); ?>
					</div>
					<div class="form-group col-md-12">
						<label></label>
						<input type="submit" class="btn btn-primary" value="Submit">
					</div>
				</div>
				<input type="hidden" name="action" value="savefiles">
			</form>

			<form name="" class="col-md-4" method="POST" id="stockfiles">
				<h2>Step 2: Save Stocks</h2>
				<p>Save Stocks is required when updating new stock, because it will take approx. (10-15mins) to complete.</p>
				<div class="row">
					<div class="form-group col-md-12">
						<label></label>
						<input type="submit" class="btn btn-primary" value="Save Stocks!">
					</div>
				</div>
				<input type="hidden" name="action" value="savestocks">
				<input type="hidden" name="process_id" value="" class="processid">
			</form>
		<?php endif; ?>

		<div class="progress-bar-wrap">
			<div class="col-md-6">
				<h2>Step 3: Import</h2>
				<span class="progress-num"><span class="inum">0</span>%</span>
				<progress class="progress-bar mb-2" value="" max="100"></progress>
				<button class="btn btn-primary process-import">Import!</button>
			</div>
		</div>

		<?php 
			$page = (isset($_GET['page']))? $_GET['page'] : 1;
			$limit = isset($_GET['limit'])? $_GET['limit'] : 10;
		?>

		<script type="text/javascript">
			(function($){
				var process_id = '<?php echo (isset($_REQUEST['direct']))? $_REQUEST['direct'] : ''; ?>';
				if(process_id == ''){
					$('.progress-bar-wrap').hide();
				}
				$('#stockfiles').hide();

				$('#csvfiles').submit(function(e){
					e.preventDefault();
					var noError = true;

					$('select',this).each(function(){
						if($(this).val() == ''){
							noError = false;
						}
					});

					if(noError){
						var formData  = $(this).serialize();
						$.post("process.php",formData,function(data){
							if(data.success){
								process_id = data.result;
								$('#csvfiles').hide();
								$('#stockfiles').show();
								$('.processid').val(process_id);
								$('.progress-bar-wrap').show();
							}
							else{
								alert(data.error);
								$('#csvfiles').show();
								$('.processid').val('');
								$('.progress-bar-wrap').hide();
							}
						},'json');
					}
					else{
						alert('Select all fields!');
					}
					return false;
				});


				$('#stockfiles').submit(function(e){
					e.preventDefault();
					
					if($('.processid').val() != ''){
						var formData  = $(this).serialize();
						$.post("process.php",formData,function(data){
							if(data.success){
								process_id = data.result;
							}
							else{
								alert(data.error);
							}
						},'json');
					}
					else{
						alert('Process ID required!');
					}
					return false;
				});

				$('.process-import').click(function(){					
					var firstLink = "process.php?action=run&process_id="+process_id+"&limit=<?php echo $limit; ?>&page=<?php echo $page; ?>";

					process(firstLink);

					function process(link){
						$.get(link, function(data){
					        // console.log(data);
					        if(data.success){
						        if(data.processed <= 100){
						        	$('progress').val(data.processed);
						        	var inum = (data.processed).toFixed(2);
						        	$('.progress-num .inum').text(inum);
						        	console.log(data.link);
						        	process(data.link);
						        }
						        else{
						        	$('progress').val('100');
						        	$('.progress-num .inum').text('100');
						        	console.log('done');
						        }
						    }
						    else{
						    	console.log(data.error);
						    }
					    },'json');
					}
				});
			})(jQuery);
		</script>
	</body>
</html>