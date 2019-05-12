<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='themes-list']" priority="1">
<div class="container my-4">
	<div class="row">
		<xsl:apply-templates select="content[@class='Text']" mode="theme-data"/>
	</div>
</div>
</xsl:template>

<xsl:template match="content[@class='Text']" mode="theme-data">
<xsl:element name="div">
	<xsl:attribute name="class">col-sm-6 col-md-4 col-lg-3</xsl:attribute>
	<xsl:element name="div">
		<xsl:attribute name="class">card my-2<xsl:if test="@current"> border-primary</xsl:if></xsl:attribute>
		<xsl:choose>
			<xsl:when test="thumbnail">
				<img class="card-img-top" src="{thumbnail}" alt="{@id} theme logo"/>
			</xsl:when>
			<xsl:otherwise>
				<img class="card-img-top" src="https://via.placeholder.com/640x360?text={@id}" alt="{@id} theme logo"/>
			</xsl:otherwise>
		</xsl:choose>
		<div class="card-body">
			<h5 class="card-title"><xsl:value-of select="@id"/></h5>
			<p class="card-text"><xsl:value-of select="description"/></p>
			<h6 class="card-title">Subthemes:</h6>
			<ul>
				<xsl:for-each select="subtheme">
					<li><xsl:value-of select="@name"/></li>
				</xsl:for-each>
			</ul>
			<a href="theme_edit.php?t={@id}" class="card-link">Edit theme</a>
		</div>
	</xsl:element>
</xsl:element>
</xsl:template>

</xsl:stylesheet>
