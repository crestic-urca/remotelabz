import React, { Component } from 'react';
import { components } from 'react-select';
import AsyncSelect from 'react-select/async';
import API from '../../api';
import SVG from '../Display/SVG';
import Routing from 'fos-jsrouting';
import {Tooltip, OverlayTrigger} from 'react-bootstrap';

const api = API.getInstance();

const getPath = (group) => {
    /** @var {String} path */
    let path = '';
    while (undefined !== group.parent) {
        path = group.parent.name + ' / ' + path;
        group = group.parent;
    }

    return path;
};

const ValueContainer = ({ children, ...props }) => (
  <components.ValueContainer {...props}>{children}</components.ValueContainer>
);

const Option = props => {
    return (
        <components.Option {...props}>
            <div className="d-flex">
                <div className="mr-2">
                    {props.data.type && props.data.type == 'user' ?
                        <div className="s36 mr-2">
                            <img src={"/users/" + props.data.value + "/picture?size=36"} className="rounded-circle"></img>
                        </div>
                        :
                        <div className={"avatar identicon bg-" + (props.data.id % 8 + 1) + " s36 rounded mr-2"}>{props.data.name.charAt(0).toUpperCase()}</div>
                    }
                </div>
                <div className="d-flex flex-column">
                    <div style={{lineHeight: 16 + 'px'}}>{getPath(props.data)} <span className="fw600">{props.data.name}</span></div>
                    {props.data.owner != undefined &&
                        <div>Owned by <img src={"/users/" + props.data.owner.id + "/picture?size=16"} className="rounded-circle"></img> {props.data.owner.name}</div>
                    }
                </div>
                <div className="d-flex flex-grow-1"></div>
                {(props.data.type && props.data.type == 'group') &&
                    <div className="d-flex align-items-center">
                        <OverlayTrigger placement="bottom" overlay={<Tooltip>Subgroups</Tooltip>}>
                            <div className="mr-2"><SVG name="folder-o"></SVG> {props.data.children.length}</div>
                        </OverlayTrigger>
                        {/* <OverlayTrigger placement="bottom" overlay={<Tooltip>Activities</Tooltip>}>
                            <div className="mr-2"><SVG name="bookmark"></SVG> {props.data.activities.length}</div>
                        </OverlayTrigger> */}
                        <OverlayTrigger placement="bottom" overlay={<Tooltip>Members</Tooltip>}>
                            <div><SVG name="users"></SVG> {props.data.usersCount}</div>
                        </OverlayTrigger>
                    </div>
                }
            </div>
        </components.Option>
    );
};

const SingleValue = ({ children, ...props }) => (
    <components.SingleValue {...props} className="d-flex align-items-center">
        {props.data.type && props.data.type == 'user' ?
            <div className="s24 mr-2">
                <img src={"/users/" + props.data.value + "/picture?size=24"} className="rounded-circle"></img>
            </div>
            :
            <div className={"avatar identicon bg-" + (props.data.id % 8 + 1) + " s24 rounded mr-2"} style={{fontSize: 12 + 'px'}}>{props.data.name.charAt(0).toUpperCase()}</div>
        }
        {/* <div className={"avatar identicon bg-" + (props.data.id % 8 + 1) + " s24 rounded mr-2"} style={{fontSize: 12 + 'px'}}>{props.data.name.charAt(0).toUpperCase()}</div> */}
        <div>
            {getPath(props.data)} <span className="fw600">{props.data.name}</span>
        </div>
    </components.SingleValue>
);

export default class GroupSelect extends Component {
    constructor(props) {
        super(props);
    }

    loadOptions = (inputValue) => {
        return api.get(Routing.generate('api_groups'), {
            params: {
                search: inputValue,
                context: 'group_explore'
            }
        })
        .then(response => {
            return response.data.map(group => {
                return {...group, value: group.id, label: group.name};
            });
        });
    }

    render() {
        return (
            <AsyncSelect
                loadOptions={this.props.loadOptions || this.loadOptions}
                className={'react-select-container ' + (this.props.className || "")}
                classNamePrefix="react-select"
                cacheOptions
                defaultOptions={this.props.defaultOptions || true}
                placeholder={this.props.placeholder || "Search for a group"}
                components={{ ValueContainer, Option, SingleValue }}
                isSearchable
                name={this.props.fieldName || "_group"}
                isClearable={this.props.isClearable || false}
                {...this.props}
            />
        );
    }
}