import{r as t,$ as e}from"../../lit-element-69ea4448.js";import{D as i}from"../dt-multi-select/dt-multi-select.js";import"../../directive-de55b00a.js";import"../dt-form-base.js";import"../dt-label/dt-label.js";import"../../icons/dt-spinner.js";import"../../icons/dt-checkmark.js";class s extends i{static get properties(){return{...super.properties,allowAdd:{type:Boolean},onload:{type:String}}}static get styles(){return[...super.styles,t`.selected-option a,.selected-option a:active,.selected-option a:visited{text-decoration:none;color:var(--primary-color,#3f729b)}`]}firstUpdated(){this._filterOptions()}_clickOption(t){if(t.target&&t.target.value){const e=t.target.value,i=this.filteredOptions.reduce(((t,i)=>t||i.id!==e?t:i),null);this._select(i),this._filterOptions()}}_clickAddNew(t){t.target&&this._select({id:"",label:t.target.dataset?.label,isNew:!0})}_remove(t){t.target&&t.target.dataset&&t.target.dataset.value&&(this.value=(this.value||[]).map((e=>{const i={...e};return e.id===t.target.dataset.value&&(i.delete=!0),i})),this._filterOptions(),this.open&&this.shadowRoot.querySelector("input").focus())}_keyboardSelectOption(){this.activeIndex>-1&&(this.activeIndex+1>this.filteredOptions.length?this._select({id:"",label:this.query,isNew:!0}):this._select(this.filteredOptions[this.activeIndex]),this._filterOptions())}_listHighlightNext(){this.activeIndex=Math.min(this.filteredOptions.length,this.activeIndex+1)}_filterOptions(){const t=(this.value||[]).filter((t=>!t.delete)).map((t=>t?.id));if(this.options?.length)this.filteredOptions=(this.options||[]).filter((e=>!t.includes(e.id)&&(!this.query||e.label.toLocaleLowerCase().includes(this.query.toLocaleLowerCase()))));else{this.loading=!0,this.filteredOptions=[];const e=this,i=new CustomEvent("load",{bubbles:!0,detail:{field:this.name,query:this.query,onSuccess:i=>{e.loading=!1,e.filteredOptions=i.filter((e=>!t.includes(e.id)))},onError:t=>{console.warn(t),e.loading=!1}}});this.dispatchEvent(i)}return this.filteredOptions}_renderOptions(){let t=super._renderOptions();return this.allowAdd&&this.query&&(Array.isArray(t)||(t=[t]),t.push(e`<li><button data-label="${this.query}" @click="${this._clickAddNew}" @touchstart="${this._touchStart}" @touchmove="${this._touchMove}" @touchend="${this._touchEnd}" class="${this.activeIndex>-1&&this.activeIndex>=this.filteredOptions.length?"active":""}">Add "${this.query}"</button></li>`)),t}_renderSelectedOptions(){return(this.value||[]).filter((t=>!t.delete)).map((t=>e`<div class="selected-option"><a href="${t.link}" ?disabled="${this.disabled}" alt="${t.status?t.status.label:t.label}">${t.label}</a> <button @click="${this._remove}" ?disabled="${this.disabled}" data-value="${t.id}">x</button></div>`))}}window.customElements.define("dt-tags",s);export{s as D};
