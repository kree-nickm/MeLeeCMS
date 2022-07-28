<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<!-- BEGIN full page HTML. -->
<xsl:template match="/MeLeeCMS">
<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html&gt;</xsl:text>
<html>
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
<title><xsl:value-of select="title"/></title>
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png"/>
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png"/>
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png"/>
<link rel="manifest" href="/site.webmanifest"/>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css" integrity="sha384-zCbKRCUGaJDkqS1kPbPd7TveP5iyJE0EjAuZQTgFLD2ylzuqKfdKlfG/eSrtxUkn" crossorigin="anonymous"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.2/css/bootstrap-select.min.css"/>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous"/>
<xsl:for-each select="css[href!='']">
	<xsl:element name="link">
		<xsl:attribute name="type">text/css</xsl:attribute>
		<xsl:attribute name="rel">stylesheet</xsl:attribute>
		<xsl:attribute name="href"><xsl:value-of select="href"/></xsl:attribute>
      <xsl:for-each select="attrs/*">
         <xsl:attribute name="{@original_tag}"><xsl:value-of select="."/></xsl:attribute>
      </xsl:for-each>
	</xsl:element>
</xsl:for-each>
<xsl:for-each select="css[code!='']">
	<style><xsl:value-of select="code"/></style>
</xsl:for-each>
</head>
<body id="MeLeeCMS" class="MeLeeCMS">

<xsl:if test="content[@id='branding'] or content[@id='nav']">
<nav class="shadow navbar navbar-expand-sm navbar-dark bg-dark">
   <xsl:if test="content[@id='branding']">
      <div class="dropdown">
         <a class="navbar-brand dropdown-toggle" href="#" role="button" id="user_menu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <xsl:if test="content[@id='branding']/logo"><img class="logo" src="{content[@id='branding']/logo}"/></xsl:if>
            <xsl:if test="content[@id='branding']/text"><xsl:value-of select="content[@id='branding']/text"/></xsl:if>
         </a>
         <div class="dropdown-menu" aria-labelledby="user_menu">
            <a class="dropdown-item" href="?logout">Logout</a>
         </div>
      </div>
   </xsl:if>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"><i class="fas fa-bars"></i></span></button>
	<div class="collapse navbar-collapse" id="navbarSupportedContent">
		<xsl:apply-templates select="content[@id='nav']"/>
	</div>
   <xsl:if test="data/user/class='MeLeeCMS\MeLeeCMSUser' and not(data/user/logged)">
      <button class="nav-link btn btn-secondary ml-1" data-toggle="modal" data-target="#login_popup">Login</button>
      <a class="nav-link btn btn-secondary ml-1" href="{url_path}register">Register</a>
   </xsl:if>
   <xsl:if test="data/user/permissions/view_xml">
      <a class="nav-link btn btn-sm btn-outline-secondary" href="?output=xml" target="_blank">XML</a>
   </xsl:if>
   <xsl:if test="data/user/permissions/view_errors and data/errors">
      <button class="nav-link btn btn-outline-danger ml-1" data-toggle="modal" data-target="#errors_popup">!</button>
   </xsl:if>
</nav>
</xsl:if>

<xsl:apply-templates select="content[@notification]"/>
<xsl:apply-templates select="content[not(@hidden) and @id!='branding' and @id!='nav' and not(@notification)]"/>

<xsl:if test="data/user/class='MeLeeCMS\MeLeeCMSUser' and not(data/user/logged)">
<div class="modal fade" id="login_popup" tabindex="-1" role="dialog" aria-labelledby="login_popup_label">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="login_popup_label">
					Login
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span class="fas fa-times" aria-hidden="true"></span>
				</button>
			</div>
			<form method="post">
				<div class="modal-body">
					<input class="form-control" type="text" name="username" placeholder="username"/>
					<input class="form-control" type="password" name="password" placeholder="password"/>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-success">Login</button>
				</div>
			</form>
		</div>
	</div>
</div>
</xsl:if>

<xsl:if test="data/user/permissions/view_errors and data/errors">
<div class="modal fade" id="errors_popup" tabindex="-1" role="dialog" aria-labelledby="errors_popup_label">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="errors_popup_label">
					Errors
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span class="fas fa-times" aria-hidden="true"></span>
				</button>
			</div>
         <div class="modal-body">
            <xsl:for-each select="data/errors">
               <div class="my-2">
                  <span class="badge badge-danger mr-1"><xsl:value-of select="type"/></span><xsl:value-of select="message"/>
                  <xsl:for-each select="stack">
                     <div class="small pl-3">
                        - <xsl:value-of select="class"/><xsl:value-of select="type"/><xsl:value-of select="function"/>(...) in <xsl:value-of select="file"/> on line <xsl:value-of select="line"/>
                     </div>
                  </xsl:for-each>
               </div>
            </xsl:for-each>
         </div>
         <div class="modal-footer">
         </div>
		</div>
	</div>
</div>
</xsl:if>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-fQybjgWLrvvRgtW6bFlB7jaZrFsaBXjsOMm/tB9LTS58ONXgqbR9W8oWht/amnpF" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.2/js/bootstrap-select.min.js"></script>
<xsl:for-each select="js">
	<xsl:element name="script">
		<xsl:attribute name="type">text/javascript</xsl:attribute>
		<xsl:if test="src!=''"><xsl:attribute name="src"><xsl:value-of select="src"/></xsl:attribute></xsl:if>
      <xsl:for-each select="attrs/*">
         <xsl:attribute name="{@original_tag}"><xsl:value-of select="."/></xsl:attribute>
      </xsl:for-each>
		<xsl:value-of select="code"/>
	</xsl:element>
</xsl:for-each>
</body>
</html>
</xsl:template>
<!-- END full page HTML. -->

<xsl:template match="content[@notification]">
<!-- TODO: These need to popup on page load. -->
<div class="modal fade auto-open" tabindex="-1" role="dialog" aria-labelledby="notification{@id}">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="notification{@id}">
					<xsl:if test="@title"><xsl:value-of select="@title"/></xsl:if>
					<xsl:if test="not(@title)">Notice</xsl:if>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span class="fas fa-times" aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
				<xsl:for-each select="*">
					<xsl:element name="div">
						<xsl:attribute name="class">alert <xsl:if test="@type">alert-<xsl:value-of select="@type"/></xsl:if><xsl:if test="not(@type)">alert-primary</xsl:if></xsl:attribute>
						<xsl:attribute name="role">alert</xsl:attribute>
						<xsl:value-of select="."/>
					</xsl:element>
				</xsl:for-each>
			</div>
			<div class="modal-footer">
			</div>
		</div>
	</div>
</div>
</xsl:template>

</xsl:stylesheet>
