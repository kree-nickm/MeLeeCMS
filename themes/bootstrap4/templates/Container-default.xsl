<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

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

</xsl:stylesheet>
