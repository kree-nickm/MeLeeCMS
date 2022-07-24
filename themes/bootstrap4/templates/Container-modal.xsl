<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@class='Container']" mode="modal">
<xsl:call-template name="modal"/>
</xsl:template>

<xsl:template match="content[@class='Container' and @subtheme='modal']">
<xsl:call-template name="modal"/>
</xsl:template>

<xsl:template name="modal">
<xsl:if test="@button">
   <xsl:call-template name="modal-toggle"/>
</xsl:if>
<div class="modal fade {@modal-classes}" id="{@id}_popup" tabindex="-1" role="dialog" aria-labelledby="{@id}_popup_label">
	<div class="modal-dialog {@modal-dialog-classes}" role="document">
		<div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="{@id}_popup_label">
               <xsl:value-of select="title"/>
            </h5>
            <xsl:if test="content[@id='subtitle']"><h6 class="text-muted"><xsl:value-of select="content[@id='subtitle']"/></h6></xsl:if>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
               <span class="fas fa-times" aria-hidden="true"></span>
            </button>
         </div>
         <div class="modal-body">
            <xsl:apply-templates select="content[@id!='subtitle' and not(@type='footer')]"/>
         </div>
         <xsl:if test="content[@type='footer']">
            <div class="modal-footer">
               <xsl:apply-templates select="content[@id!='subtitle' and @type='footer']"/>
            </div>
         </xsl:if>
		</div>
	</div>
</div>
</xsl:template>

<xsl:template name="modal-toggle">
<button class="btn btn-primary" data-toggle="modal" data-target="#{@id}_popup">
   <xsl:if test="toggle-text">
      <xsl:value-of select="toggle-text"/>
   </xsl:if>
   <xsl:if test="not(toggle-text)">
      <xsl:value-of select="title"/>
   </xsl:if>
</button>
</xsl:template>

</xsl:stylesheet>
