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
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css" integrity="sha384-zCbKRCUGaJDkqS1kPbPd7TveP5iyJE0EjAuZQTgFLD2ylzuqKfdKlfG/eSrtxUkn" crossorigin="anonymous"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.2/css/bootstrap-select.min.css"/>
<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous"/>
<xsl:for-each select="css[href!='']">
	<xsl:element name="link">
		<xsl:attribute name="type">text/css</xsl:attribute>
		<xsl:attribute name="rel">stylesheet</xsl:attribute>
		<xsl:attribute name="href"><xsl:value-of select="href"/></xsl:attribute>
	</xsl:element>
</xsl:for-each>
<xsl:for-each select="css[code!='']">
	<style><xsl:value-of select="code"/></style>
</xsl:for-each>
</head>
<body id="MeLeeCMSBody" class="">

<xsl:if test="content[@id='branding'] or content[@id='nav']">
<nav class="navbar navbar-expand-sm navbar-dark">
	<a class="navbar-brand" href="index.php"><xsl:value-of select="content[@id='branding']"/></a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
	<div class="collapse navbar-collapse" id="navbarSupportedContent">
		<ul class="navbar-nav mr-auto">
			<xsl:for-each select="content[@id='nav']/content">
				<xsl:choose>
				<xsl:when test="@type='dropdown'">
					<xsl:element name="li">
						<xsl:attribute name="class">nav-item dropdown<xsl:if test="@active!=''"> active</xsl:if></xsl:attribute>
						<xsl:element name="a">
							<xsl:attribute name="class">nav-link dropdown-toggle</xsl:attribute>
							<xsl:attribute name="href">#</xsl:attribute>
							<xsl:attribute name="role">button</xsl:attribute>
							<xsl:attribute name="data-toggle">dropdown</xsl:attribute>
							<xsl:attribute name="aria-haspopup">true</xsl:attribute>
							<xsl:attribute name="aria-expanded">false</xsl:attribute>
							<xsl:attribute name="id">ndd<xsl:value-of select="@id"/></xsl:attribute>
							<xsl:value-of select="text"/>
							<xsl:if test="badge"><span class="badge badge-secondary ml-1"><xsl:value-of select="badge"/></span></xsl:if>
							<xsl:if test="@active!=''"><span class="sr-only">(current)</span></xsl:if>
						</xsl:element>
						<div class="dropdown-menu" aria-labelledby="ndd{@id}">
							<xsl:for-each select="sublink">
								<xsl:element name="a">
									<xsl:attribute name="class">dropdown-item<xsl:if test="@active!=''"> active</xsl:if></xsl:attribute>
									<xsl:attribute name="href"><xsl:value-of select="url"/></xsl:attribute>
									<xsl:value-of select="text"/>
									<xsl:if test="badge"><span class="badge badge-primary ml-1"><xsl:value-of select="badge"/></span></xsl:if>
									<xsl:if test="@active!=''"><span class="sr-only">(current)</span></xsl:if>
								</xsl:element>
							</xsl:for-each>
						</div>
					</xsl:element>
				</xsl:when>
				<xsl:otherwise>
					<xsl:element name="li">
						<xsl:attribute name="class">nav-item<xsl:if test="@active!=''"> active</xsl:if></xsl:attribute>
						<xsl:element name="a">
							<xsl:attribute name="class">nav-link</xsl:attribute>
							<xsl:attribute name="href"><xsl:value-of select="url"/></xsl:attribute>
							<xsl:value-of select="text"/>
							<xsl:if test="badge"><span class="badge badge-secondary ml-1"><xsl:value-of select="badge"/></span></xsl:if>
							<xsl:if test="@active!=''"><span class="sr-only">(current)</span></xsl:if>
						</xsl:element>
					</xsl:element>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
		</ul>
	</div>
   <xsl:if test="not(data/user/logged)">
      <button class="nav-link btn btn-secondary ml-1" data-toggle="modal" data-target="#login_popup">Login</button>
      <a class="nav-link btn btn-secondary ml-1" href="{url_path}register">Register</a>
   </xsl:if>
   <xsl:if test="data/user/logged">
      <a class="nav-link btn btn-sm btn-outline-secondary" href="?output=xml" target="_blank">XML</a>
   </xsl:if>
</nav>
</xsl:if>

<xsl:apply-templates select="content[@notification]"/>
<xsl:apply-templates select="content[not(@hidden) and @id!='branding' and @id!='nav' and not(@notification) and @class!='Data']"/>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-fQybjgWLrvvRgtW6bFlB7jaZrFsaBXjsOMm/tB9LTS58ONXgqbR9W8oWht/amnpF" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.2/js/bootstrap-select.min.js"></script>
<xsl:for-each select="js">
	<xsl:element name="script">
		<xsl:attribute name="type">text/javascript</xsl:attribute>
		<xsl:if test="src and src!=''"><xsl:attribute name="src"><xsl:value-of select="src"/></xsl:attribute></xsl:if>
		<xsl:value-of select="code"/>
	</xsl:element>
</xsl:for-each>
</body>
</html>
</xsl:template>
<!-- END full page HTML. -->

<!-- Default display of a Container component with content. -->
<xsl:template match="content[@class='Container']">
<div class="container my-4">
	<div class="card border-dark">
		<div class="card-header text-white bg-dark">
			<h4 class="card-title"><xsl:value-of select="title"/></h4>
			<xsl:if test="content[@id='subtitle']"><h6 class="card-subtitle text-muted"><xsl:value-of select="content[@id='subtitle']"/></h6></xsl:if>
		</div>
		<xsl:element name="div">
			<xsl:attribute name="class">
				card-body no-footer
				<xsl:if test="@nopadding">p-0</xsl:if>
			</xsl:attribute>
			<xsl:apply-templates select="content[@id!='subtitle']"/>
		</xsl:element>
	</div>
</div>
</xsl:template>

<!-- Default display of content text. -->
<xsl:template match="content[@class='Text']">
<xsl:if test="not(*)">
	<p><xsl:value-of select="."/></p>
</xsl:if>
<xsl:if test="*">
	<p><xsl:for-each select="*">
		<xsl:value-of select="."/>
		<xsl:if test="position()!=last()"><br/></xsl:if>
	</xsl:for-each></p>
</xsl:if>
</xsl:template>

<xsl:template match="content[@class='Text' and @raw]">
<xsl:value-of select="."/>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='select']">
<xsl:param name="id"/>
<xsl:element name="select">
	<xsl:attribute name="class">form-control selectpicker</xsl:attribute>
	<xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute>
	<xsl:for-each select="option">
		<xsl:element name="option">
			<xsl:if test="@value">
				<xsl:attribute name="value"><xsl:value-of select="@value"/></xsl:attribute>
				<xsl:if test="../value=@value"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
			</xsl:if>
			<xsl:if test="not(@value)">
				<xsl:attribute name="value"><xsl:value-of select="."/></xsl:attribute>
				<xsl:if test="../value=."><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
			</xsl:if>
			<xsl:value-of select="."/>
		</xsl:element>
	</xsl:for-each>
</xsl:element>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='input-text']">
<xsl:param name="id"/>
<xsl:element name="input">
	<xsl:attribute name="class">form-control</xsl:attribute>
	<xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute>
	<xsl:attribute name="type">text</xsl:attribute>
	<xsl:attribute name="value"><xsl:value-of select="value"/></xsl:attribute>
	<xsl:if test="size"><xsl:attribute name="size"><xsl:value-of select="size"/></xsl:attribute></xsl:if>
</xsl:element>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='submit']">
<xsl:element name="button">
	<xsl:attribute name="class">form-control btn btn-success</xsl:attribute>
	<xsl:attribute name="type">submit</xsl:attribute>
	<xsl:value-of select="text"/>
</xsl:element>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='link']">
<xsl:element name="a">
	<xsl:attribute name="class"><xsl:value-of select="@css-class"/></xsl:attribute>
	<xsl:attribute name="href"><xsl:value-of select="url"/></xsl:attribute>
	<xsl:value-of select="text"/>
</xsl:element>
</xsl:template>

<!-- A text block with alert styling -->
<xsl:template match="content[@class='Text' and @alert]">
<div class="alert alert-{@alert}" role="alert">
	<xsl:value-of select="."/>
</div>
</xsl:template>

<xsl:template match="content[@class='DatabaseView']">
<div class="table-responsive">
	<xsl:element name="table">
		<xsl:attribute name="class">
			table table-striped
			<xsl:if test="@nomargin">m-0</xsl:if>
			<xsl:if test="@small">table-sm</xsl:if>
			<xsl:if test="@hover">table-hover</xsl:if>
		</xsl:attribute>
		<caption><xsl:value-of select="table"/> (<xsl:value-of select="count"/> rows)</caption>
		<thead class="thead-light">
			<tr>
			<xsl:for-each select="row[1]/*">
				<th><xsl:value-of select="name()"/></th>
			</xsl:for-each>
			</tr>
		</thead>
		<tbody>
		<xsl:for-each select="row">
			<tr>
			<xsl:for-each select="*">
				<td><xsl:value-of select="."/></td>
			</xsl:for-each>
			</tr>
		</xsl:for-each>
		</tbody>
	</xsl:element>
</div>
</xsl:template>

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
