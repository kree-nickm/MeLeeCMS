<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:variable name="ContentClasses" select="/MeLeeCMS/content[@id='content-classes']/class"/>

<xsl:template match="content[@id='component_edit']" priority="1">
<form id="form_edit_page" action="component_save.php" method="post">
	<input type="hidden" value="{content[@id='props']/index}" name="component_index"/>
	<div class="container my-4">
		<noscript><div class="alert alert-danger" role="alert">The page editor requires JavaScript in order to function.</div></noscript>
		<div class="card border-dark">
			<div class="card-header text-white bg-dark">
				<h4 class="card-title">Create/Edit Component</h4>
				<h6 class="card-subtitle text-muted">Make changes to the properties and content of this component.</h6>
			</div>
			<div class="card-body row">
				<div class="form-group col-lg-6">
					<input type="text" class="form-control" name="component_title" value="{content[@id='props']/title}" placeholder="Title"/>
				</div>
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
			</div>
		</div>
	</div>
	
	<div class="container my-2 form-inline">
		<div class="alert alert-info" role="alert">Be careful when adding components to other components. It is possible to create an infinite loop (component A includes component B, then component B includes component A, etc.) that will break any page that uses any of those components.</div>
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
	
	<div class="container my-2 form-inline">
		<input class="btn btn-success mx-auto" type="submit" value="Save Component"/>
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
