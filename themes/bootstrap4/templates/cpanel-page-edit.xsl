<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='page_edit']" priority="1">
<xsl:variable name="ContentClasses" select="/MeLeeCMS/content[@id='content-classes']/class"/>
<form id="form_edit_page" action="page_save.php" method="post">
	<xsl:if test="content[@id='props']/index"><input type="hidden" value="{content[@id='props']/index}" name="page_index"/></xsl:if>
	<xsl:if test="content[@id='props']/sindex"><input type="hidden" value="{content[@id='props']/sindex}" name="page_special_index"/></xsl:if>
	<div class="container my-4">
		<noscript><div class="alert alert-danger" role="alert">The page editor requires JavaScript in order to function.</div></noscript>
		<div class="card border-dark">
			<div class="card-header text-white bg-dark">
				<h4 class="card-title">Create/Edit Page</h4>
				<h6 class="card-subtitle text-muted">Make changes to the properties and content of this page.</h6>
			</div>
			<div class="card-body row">
				<div class="input-group form-group col-md-6 col-lg-4" title="The title of the page that will appear in the browser tab. Can by overwritten in PHP files.">
					<input type="text" class="form-control" name="page_title" value="{content[@id='props']/title}" placeholder="Page Title"/>
					<div class="input-group-append">
						<span class="input-group-text"> - <xsl:value-of select="content[@id='props']/site_title"/></span>
					</div>
				</div>
			<xsl:if test="content[@id='props']/url_path">
				<div class="input-group form-group col-md-6 col-lg-4" title="The URL to access the page.">
					<div class="input-group-prepend">
						<span class="input-group-text"><xsl:value-of select="content[@id='props']/url_path"/></span>
					</div>
					<input type="text" class="form-control" name="page_url" value="{content[@id='props']/url}" placeholder="page-url"/>
				</div>
			</xsl:if>
				<div class="form-group col-md-5 col-lg-4">
					<xsl:apply-templates select="content[@id='props']/select[@id='subtheme']" mode="page-select">
						<xsl:with-param name="placeholder">Custom subtheme...</xsl:with-param>
					</xsl:apply-templates>
				</div>
			<xsl:if test="content[@id='props']/select[@id='permissions']">
				<xsl:element name="div">
					<xsl:attribute name="class">form-group col-md-7 col-lg-6</xsl:attribute>
					<xsl:apply-templates select="content[@id='props']/select[@id='permissions']" mode="page-select">
						<xsl:with-param name="placeholder">Required permissions to view page...</xsl:with-param>
					</xsl:apply-templates>
				</xsl:element>
			</xsl:if>
			<xsl:if test="not(content[@id='props']/file)">
				<div class="form-group col-lg-6">
					<xsl:apply-templates select="content[@id='props']/select[@id='page_css']" mode="page-select">
						<xsl:with-param name="placeholder">CSS files to include...</xsl:with-param>
					</xsl:apply-templates>
				</div>
				<div class="form-group col-lg-6">
					<xsl:apply-templates select="content[@id='props']/select[@id='page_js']" mode="page-select">
						<xsl:with-param name="placeholder">JavaScript files to include...</xsl:with-param>
					</xsl:apply-templates>
				</div>
				<div class="form-group col-lg-6">
					<xsl:apply-templates select="content[@id='props']/select[@id='page_xsl']" mode="page-select">
						<xsl:with-param name="placeholder">XSL templates to include...</xsl:with-param>
					</xsl:apply-templates>
				</div>
			</xsl:if>
			<xsl:if test="content[@id='props']/file">
				<div class="input-group form-group col-md-6 col-lg-4" title="The file with the PHP code for this page.">
					<div class="input-group-prepend">
						<span class="input-group-text">/includes/pages/</span>
					</div>
					<input type="text" class="form-control" name="page_file" value="{content[@id='props']/file}" placeholder="file.php"/>
				</div>
			</xsl:if>
			</div>
		</div>
	</div>
	
	<xsl:if test="not(content[@id='props']/file)">
	<div class="container my-2 form-inline">
		<div class="input-group mx-auto add-page-content">
			<select class="form-control" aria-label="Add page content">
				<xsl:for-each select="$ContentClasses">
					<option value="{.}"><xsl:value-of select="."/></option>
				</xsl:for-each>
			</select>
			<div class="input-group-append">
				<button class="btn btn-primary" type="button">Add Content</button>
			</div>
		</div>
	</div>
	
	<div class="container">
		<div id="page_content_container" class="row">
			<xsl:apply-templates select="content[@id='page_content']/content[@page_content]" mode="page-content"/>
		</div>
	</div>
	</xsl:if>
	
	<div class="container my-2 form-inline">
		<div class="mx-auto">
			<xsl:if test="not(content[@id='props']/file)"><button class="btn btn-success mx-1" type="submit" name="save" value="2">Save Draft</button></xsl:if>
			<button class="btn btn-success mx-1" type="submit" name="save" value="1">Save Page</button>
		</div>
	</div>
</form>
</xsl:template>

<xsl:template match="select" mode="page-select">
<xsl:param name="placeholder"/>
<xsl:element name="select">
	<xsl:attribute name="class">form-control selectpicker show-tick</xsl:attribute>
	<xsl:attribute name="id"><xsl:value-of select="@id"/></xsl:attribute>
	<xsl:attribute name="name"><xsl:value-of select="@id"/><xsl:if test="@multiple">[]</xsl:if></xsl:attribute>
	<xsl:attribute name="title"><xsl:value-of select="$placeholder"/></xsl:attribute>
	<xsl:attribute name="data-header"><xsl:value-of select="$placeholder"/></xsl:attribute>
	<xsl:if test="count(option)>10"><xsl:attribute name="data-actions-box">true</xsl:attribute></xsl:if>
	<xsl:if test="count(option)>20"><xsl:attribute name="data-live-search">true</xsl:attribute></xsl:if>
	<xsl:if test="@multiple"><xsl:attribute name="multiple"/></xsl:if>
	<xsl:if test="@required"><xsl:attribute name="required"/></xsl:if>
	<xsl:apply-templates select="option" mode="main-option"><xsl:with-param name="selected_value" select="value"/><xsl:sort select="."/></xsl:apply-templates>
</xsl:element>
</xsl:template>

<xsl:template match="option" mode="main-option">
<xsl:param name="selected_value"/>
<xsl:variable name="value">
	<xsl:choose>
		<xsl:when test="@value">
			<xsl:value-of select="@value"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="."/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:variable>
<xsl:element name="option">
	<xsl:if test="@subtext"><xsl:attribute name="data-subtext"><xsl:value-of select="@subtext"/></xsl:attribute></xsl:if>
	<xsl:if test="@title"><xsl:attribute name="title"><xsl:value-of select="@title"/></xsl:attribute></xsl:if>
	<xsl:if test="@html"><xsl:attribute name="data-content"><xsl:value-of select="@html"/></xsl:attribute></xsl:if>
	<xsl:attribute name="value"><xsl:value-of select="$value"/></xsl:attribute>
	<xsl:for-each select="$selected_value">
		<xsl:if test=".=$value"><xsl:attribute name="selected"/></xsl:if>
	</xsl:for-each>
	<xsl:value-of select="."/>
</xsl:element>
</xsl:template>

</xsl:stylesheet>
