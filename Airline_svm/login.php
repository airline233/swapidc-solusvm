<form action="<?php echo $_GET['sip'] ?>/login.php" method="post" name="frm">
<input name="username" value="<?php echo $_GET['username']; ?>" hidden />
<input name="password" value="<?php echo $_GET['password']; ?>" hidden />
<input name="act" value="login" hidden />
<input name="Submit" value=1 hidden />
</form>
<script>
this.frm.submit();
</script>